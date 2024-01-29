<?php
namespace smpp\transport;

use smpp\exceptions\SmppTransportException;

/**
 * TCP Stream Socket Transport for use with multiple protocols.
 * Supports connection pools and IPv6 in addition to providing a few public methods to make life easier.
 * It's primary purpose is long-running connections, since it don't support stream re-use, ip-blacklisting, etc.
 * It assumes a blocking/synchronous architecture, and will block when reading or writing, but will enforce timeouts.
 *
 * Copyright (C) 2024 Marcin Laber
 * @author marcin@laber.pl
 */
class Stream
{
	protected $stream;
	protected $hosts;
	protected $persist;
	protected $debugHandler;
	public $debug;
	
	protected static $useTls = false;
	protected static $defaultSendTimeout = 100;
	protected static $defaultRecvTimeout = 750;
	public static $defaultDebug = false;
	
	public static $forceIpv6 = false;
	public static $forceIpv4 = false;
	public static $randomHost = false;
	
	/**
	 * Construct a new stream for this transport to use.
	 */
	public function __construct (array $hosts, mixed $ports, bool $persist = false, mixed $debugHandler = null)
	{
		$this->debug = self::$defaultDebug;
		$this->debugHandler = $debugHandler ? $debugHandler : 'error_log';
		
		// Deal with optional port
		$h = [];
		foreach ($hosts as $key => $host)
			$h[] = [$host, is_array($ports) ? $ports[$key] : $ports];
		
		if (self::$randomHost)
			shuffle($h);
		
		$this->resolveHosts($h);
		
		$this->persist = $persist;
	}
	
	/**
	 * Resolve the hostnames into IPs, and sort them into IPv4 or IPv6 groups.
	 * If using DNS hostnames, and all lookups fail, a InvalidArgumentException is thrown.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function resolveHosts (array $hosts): void
	{
		$i = 0;
		foreach ($hosts as $host)
		{
			[$hostname, $port] = $host;
			$ip4s = [];
			$ip6s = [];
			
			if (preg_match('/^([12]?[0-9]?[0-9]\.){3}([12]?[0-9]?[0-9])$/', $hostname))
			{
				// IPv4 address
				$ip4s[] = $hostname;
			}
			elseif (preg_match('/^([0-9a-f:]+):[0-9a-f]{1,4}$/i', $hostname))
			{
				// IPv6 address
				$ip6s[] = $hostname;
			}
			else
			{
				// Do a DNS lookup
				if (!self::$forceIpv4)
				{
					// if not in IPv4 only mode, check the AAAA records first
					$records = dns_get_record($hostname, DNS_AAAA);
					if ($records === false && $this->debug)
						call_user_func($this->debugHandler, 'DNS lookup for AAAA records for: ' . $hostname . ' failed');
					
					if ($records)
					{
						foreach ($records as $r)
							if (isset($r['ipv6']) && $r['ipv6'])
								$ip6s[] = $r['ipv6'];
					}
					
					if ($this->debug)
						call_user_func($this->debugHandler, 'IPv6 addresses for ' . $hostname . ': ' . implode(', ', $ip6s));
				}
				if (!self::$forceIpv6)
				{
					// if not in IPv6 mode check the A records also
					$records = dns_get_record($hostname, DNS_A);
					if ($records === false && $this->debug)
						call_user_func($this->debugHandler, 'DNS lookup for A records for: ' . $hostname . ' failed');
					
					if ($records)
					{
						foreach ($records as $r)
							if (isset($r['ip']) && $r['ip'])
								$ip4s[] = $r['ip'];
					}
					
					// also try gethostbyname, since name could also be something else, such as "localhost" etc.
					$ip = gethostbyname($hostname);
					if ($ip != $hostname && !in_array($ip, $ip4s))
						$ip4s[] = $ip;
					
					if ($this->debug)
						call_user_func($this->debugHandler, 'IPv4 addresses for ' . $hostname . ': ' . implode(', ', $ip4s));
				}
			}
			
			// Did we get any results?
			if ((self::$forceIpv4 && empty($ip4s)) ||
				(self::$forceIpv6 && empty($ip6s)) ||
				(empty($ip4s) && empty($ip6s)))
				continue;
			
			if ($this->debug)
				$i += count($ip4s) + count($ip6s);
			
			// Add results to pool
			$this->hosts[] = [$hostname, $port, $ip6s, $ip4s];
		}
		
		if ($this->debug)
			call_user_func($this->debugHandler, 'Built connection pool of ' . count($this->hosts) . ' host(s) with ' . $i . ' ip(s) in total');
		
		if (empty($this->hosts))
			throw new \InvalidArgumentException('No valid hosts was found');
	}
	
	/**
	 * Get a reference to the stream.
	 * You should use the public functions rather than the stream directly
	 */
	public function getStream (): mixed
	{
		return $this->stream;
	}
	
	/**
	 * Sets the TLS mode.
	 * Throws SmppTransportException is value could not be changed
	 * @throws SmppTransportException
	 */
	public function useTls (bool $tls): void
	{
		if (!$this->isOpen())
			self::$useTls = $tls;
		else
			throw new SmppTransportException('Could not toggle TLS when connected');
	}
	
	/**
	 * Sets the send timeout.
	 * Throws SmppTransportException is value could not be changed
	 * @throws SmppTransportException
	 */
	public function setSendTimeout (int $timeout): void
	{
		if (!$this->isOpen())
			self::$defaultSendTimeout = $timeout;
		else
			throw new SmppTransportException('Could not change send timeout when connected');
	}
	
	/**
	 * Sets the receive timeout.
	 * Throws SmppTransportException is value could not be changed
	 * @throws SmppTransportException
	 */
	public function setRecvTimeout (int $timeout): void
	{
		if (!$this->isOpen())
			self::$defaultRecvTimeout = $timeout;
		else
			throw new SmppTransportException('Could not change recv timeout when connected');
	}
	
	/**
	 * Check if the stream is constructed, and there are no exceptions on it
	 * Returns false if it's closed.
	 * Throws SmppTransportException is state could not be ascertained
	 * @throws SmppTransportException
	 */
	public function isOpen (): bool
	{
		if (!is_resource($this->stream))
			return false;
		
		$r = null;
		$w = null;
		$e = [$this->stream];
		$res = stream_select($r, $w, $e, 0);
		
		if ($res === false)
			throw new SmppTransportException('Could not examine stream');
		
		// if there is an exception on our stream it's probably dead
		if (!empty($e))
			return false;
		
		return true;
	}
	
	/**
	 * Convert a milliseconds into a seconds
	 */
	private function millisecToSec (int $milliseconds): float
	{
		return $milliseconds / 1000;
	}
	
	/**
	 * Convert a milliseconds into a sec+usec array
	 */
	private function millisecToArray (int $milliseconds): array
	{
		$usec = $milliseconds * 1000;
		return ['sec' => (int)floor($usec / 1000000), 'usec' => $usec % 1000000];
	}
	
	/**
	 * Open the stream, trying to connect to each host in succession.
	 * This will prefer IPv6 connections if forceIpv4 is not enabled.
	 * If all hosts fail, a SmppTransportException is thrown.
	 *
	 * @throws SmppTransportException
	 */
	public function open (): void
	{
		$context = stream_context_create([
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
			],
		]);
		
		$it = new \ArrayIterator($this->hosts);
		while ($it->valid())
		{
			[$hostname, $port, $ip6s, $ip4s] = $it->current();
			
			if (!self::$forceIpv4 && !empty($ip6s))
			{
				// Attempt IPv6s first
				foreach ($ip6s as $ip)
				{
					if ($this->debug)
						call_user_func($this->debugHandler, "Connecting to \"$hostname\" ([$ip]:$port)...");
					
					$s = stream_socket_client((self::$useTls ? 'tls' : 'tcp') . '//[' . $ip . ']:' . $port, $err_code, $err_message, $this->millisecToSec(self::$defaultRecvTimeout), \STREAM_CLIENT_CONNECT, $context);
					if ($s)
					{
						if ($this->debug)
							call_user_func($this->debugHandler, "Connected to \"$hostname\" ([$ip]:$port)!");
						
						$this->stream = $s;
						return;
					}
					elseif ($this->debug)
						call_user_func($this->debugHandler, "Stream socket connect to \"$hostname\" ([$ip]:$port) failed; " . $err_message);
				}
			}
			
			if (!self::$forceIpv6 && !empty($ip4s))
			{
				foreach ($ip4s as $ip)
				{
					if ($this->debug)
						call_user_func($this->debugHandler, "Connecting to \"$hostname\" ($ip:$port)...");
					
					$s = stream_socket_client((self::$useTls ? 'tls' : 'tcp') . '://' . $ip . ':' . $port, $err_code, $err_message, $this->millisecToSec(self::$defaultRecvTimeout), \STREAM_CLIENT_CONNECT, $context);
					if ($s)
					{
						if ($this->debug)
							call_user_func($this->debugHandler, "Connected to \"$hostname\" ($ip:$port)!");
						
						$this->stream = $s;
						return;
					}
					elseif ($this->debug)
						call_user_func($this->debugHandler, "Stream socket connect to \"$hostname\" ($ip:$port) failed; " . $err_message);
				}
			}
			
			$it->next();
		}
		throw new SmppTransportException('Could not connect to any of the specified hosts');
	}
	
	/**
	 * Do a clean shutdown of the stream.
	 */
	public function close (): void
	{
		stream_set_blocking($this->stream, true);
		
		$r = null;
		$w = [$this->stream];
		$e = null;
		stream_select($r, $w, $e, 1);
		
		stream_socket_shutdown($this->stream, \STREAM_SHUT_RDWR);
		fclose($this->stream);
	}
	
	/**
	 * Check if there is data waiting for us on the wire
	 * @throws SmppTransportException
	 */
	public function hasData (): bool
	{
		$r = [$this->stream];
		$w = null;
		$e = null;
		$res = stream_select($r, $w, $e, 0);
		if ($res === false)
			throw new SmppTransportException('Could not examine stream');
		
		if (!empty($r))
			return true;
		
		return false;
	}
	
	/**
	 * Read up to $length bytes from the stream.
	 * Does not guarantee that all the bytes are read.
	 * Returns false on EOF
	 * Returns false on timeout (technically EAGAIN error).
	 * Throws SmppTransportException if data could not be read.
	 *
	 * @throws SmppTransportException
	 */
	public function read (int $length): mixed
	{
		$d = stream_get_contents($this->stream, $length);
		
		if ($d === false)
			throw new SmppTransportException('Could not read ' . $length . ' bytes from stream');
		
		if ($d === '')
			return false;
		
		return $d;
	}
	
	/**
	 * Read all the bytes, and block until they are read.
	 * Timeout throws SmppTransportException
	 *
	 * @throws SmppTransportException
	 */
	public function readAll (int $length): string
	{
		$d = '';
		$r = 0;
		$readTimeout = $this->millisecToArray(self::$defaultRecvTimeout);
		stream_set_timeout($this->stream, $readTimeout['sec'], $readTimeout['usec']);
		
		while ($r < $length)
		{
			$buf = stream_get_contents($this->stream, $length - $r);
			if ($buf === false)
				throw new SmppTransportException('Could not read ' . $length . ' bytes from stream');
			
			$d .= $buf;
			if (strlen($d) === $length)
				return $d;
			
			// wait for data to be available, up to timeout
			$r = [$this->stream];
			$w = null;
			$e = [$this->stream];
			$res = stream_select($r, $w, $e, $readTimeout['sec'], $readTimeout['usec']);
			
			// check
			if ($res === false)
				throw new SmppTransportException('Could not examine stream');
			
			if (!empty($e))
				throw new SmppTransportException('Stream socket exception while waiting for data');
			
			if (empty($r))
				throw new SmppTransportException('Timed out waiting for data on stream');
		}
	}
	
	/**
	 * Write (all) data to the stream.
	 * Timeout throws SmppTransportException
	 *
	 * @throws SmppTransportException
	 */
	public function write (string $buffer, int $length): void
	{
		$r = $length;
		$writeTimeout = $this->millisecToArray(self::$defaultSendTimeout);
		stream_set_timeout($this->stream, $writeTimeout['sec'], $writeTimeout['usec']);
		
		while ($r > 0)
		{
			$wrote = fwrite($this->stream, $buffer, $r);
			if ($wrote === false)
				throw new SmppTransportException('Could not write ' . $length . ' bytes to stream');
			
			$r -= $wrote;
			if ($r === 0)
				return;
			
			$buffer = substr($buffer, $wrote);
			
			// wait for the socket to accept more data, up to timeout
			$r = null;
			$w = [$this->stream];
			$e = [$this->stream];
			$res = stream_select($r, $w, $e, $writeTimeout['sec'], $writeTimeout['usec']);
			
			// check
			if ($res === false)
				throw new SmppTransportException('Could not examine stream');
			
			if (!empty($e))
				throw new SmppTransportException('Stream socket exception while waiting to write data');
			
			if (empty($w))
				throw new SmppTransportException('Timed out waiting to write data on stream');
		}
	}
}