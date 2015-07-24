<?php

namespace Vscn\Snowflake;

class IdWorker
{
    const WORKER_ID_BITS     = 5;
    const DATACENTER_ID_BITS = 5;
    const SEQUENCE_BITS      = 12;
    const TWEPOC = 1288834974657;

    protected $workerId;
    protected $datacenterId;
    protected $sequence;
    protected $lastTimestamp = -1;

    public function __construct($workerId, $datacenterId, $sequence = 0)
    {
        $this->workerId     = $workerId;
        $this->datacenterId = $datacenterId;
        $this->sequence     = $sequence;
    }

    /**
     * Return the next Snowflake ID.
     *
     * @return biginteger
     */
    public function nextId()
    {
        $timestamp = $this->getTimestamp();

        if ($timestamp < $this->lastTimestamp) {
            throw new InvalidSystemClockException(sprintf("Clock moved backwards. Refusing to generate id for %d milliseconds", ($this->lastTimestamp - $timestamp)));
        }

        if ($timestamp == $this->lastTimestamp) {
            $sequence = $this->nextSequence() & $this->sequenceMask();

            // sequence rollover, wait til next millisecond
            if ($sequence == 0) {
                $timestamp = $this->tilNextMillis($this->lastTimestamp);
            }
        } else {
            $this->sequence = 0;
            $sequence = $this->nextSequence();
        }

        $this->lastTimestamp = $timestamp;
        $t = floor($timestamp - self::TWEPOC) << $this->timestampLeftShift();
        $dc = $this->getDatacenterId() << $this->datacenterIdShift();
        $worker = $this->getWorkerId() << $this->workerIdShift();

        return PHP_INT_SIZE === 4 ? $this->mintId32($t, $dc, $worker, $sequence) : $this->mintId64($t, $dc, $worker, $sequence);
    }

    /**
     * Return timestamp in miliseconds
     *
     * @return integer
     */
    public function getTimestamp()
    {
        return floor(microtime(true) * 1000);
    }

    /**
     * Return the Worker Id
     *
     * @return integer
     */
    public function getWorkerId()
    {
        return $this->workerId;
    }

    /**
     * Return the Datacenter ID
     *
     * @return integer
     */
    public function getDatacenterId()
    {
        return $this->datacenterId;
    }

    /**
     * Increments and return the sequence.
     *
     * @return integer
     */
    protected function nextSequence()
    {
        return $this->sequence++;
    }

    private function maxWorkerId()
    {
        return -1 ^ (-1 << self::WORKER_ID_BITS);
    }

    private function maxDatacenterId()
    {
        return -1 ^ (-1 << self::DATACENTER_ID_BITS);
    }

    private function workerIdShift()
    {
        return self::SEQUENCE_BITS;
    }

    private function datacenterIdShift()
    {
        return self::SEQUENCE_BITS + self::WORKER_ID_BITS;
    }

    private function timestampLeftShift()
    {
        return self::SEQUENCE_BITS + self::WORKER_ID_BITS + self::DATACENTER_ID_BITS;
    }

    private function sequenceMask()
    {
        return -1 ^ (-1 << self::SEQUENCE_BITS);
    }

    private function mintId32()
    {
        return null;
    }

    private function mintId64($timestamp, $datacenterId, $workerId, $sequence)
    {
        return (string)$timestamp | $datacenterId | $workerId | $sequence;
    }

    protected function tilNextMillis($lastTimestamp)
    {
        $timestamp = $this->getTimestamp();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->getTimestamp();
        }

        return $timestamp;
    }
}