<?php

namespace spec\LucasVscn\Snowflake;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class IdWorkerSpec extends ObjectBehavior
{
    protected $workerId;
    protected $datacenterId;

    function let()
    {
        $this->workerId = 1;
        $this->datacenterId = 1;
        $this->beConstructedWith($this->workerId, $this->datacenterId);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LucasVscn\Snowflake\IdWorker');
    }

    function it_genereate_an_id()
    {
        $this->nextId()->shouldNotBe(0);
    }

    function it_return_an_accurate_timestamp()
    {
        $t = floor(microtime(true) * 1000);

        $this->beAnInstanceOf('LucasVscn\Snowflake\EasyTimeWorker');
        $this->beConstructedWith(1, 1);
        $this->timestamp = $t;

        $this->getTimestamp()->shouldBe($t);
    }

    function it_return_the_correct_worker_id()
    {
        $this->getWorkerId()->shouldBe($this->workerId);
    }

    function it_return_the_correct_dc_id()
    {
        $this->getDataCenterId()->shouldBe($this->datacenterId);
    }

    function it_properly_mask_worker_id()
    {
        // setup
        $workerId     = 0x1F;
        $datacenterId = 1;
        $mask = 0x000000000001F000;

        $this->beConstructedWith($workerId, $datacenterId);

        // tests ...
        for ($i = 0; $i < 10; $i++) {
            $this->nextId()->shouldBeProperlyMasked($workerId, $mask, 12);
        }
    }

    function it_properly_mask_dc_id()
    {
        // setup
        $workerId     = 0;
        $datacenterId = 0x1F;
        $mask = 0x00000000003E0000;

        $this->beConstructedWith($workerId, $datacenterId);

        // tests ...
        for ($i = 0; $i < 10; $i++) {
            $this->nextId()->shouldBeProperlyMasked($datacenterId, $mask, 17);
        }
    }

    function it_properly_mask_timestamp()
    {
        // setup
        $workerId     = 31;
        $datacenterId = 31;
        $mask = 0xFFFFFFFFFFC00000;

        $this->beAnInstanceOf('LucasVscn\Snowflake\EasyTimeWorker');
        $this->beConstructedWith($workerId, $datacenterId);

        for ($i = 0; $i < 1; $i++) {
            $timestamp = floor(microtime(true) * 1000);
            $this->timestamp = $timestamp;
            $this->getTimestamp()->shouldBe($timestamp);
            $this->nextId()->shouldBeProperlyMasked(($timestamp - \LucasVscn\Snowflake\IdWorker::TWEPOC), $mask, 22);
        }
    }

    function it_roll_over_sequence_id()
    {
         // put a zero in the low bit so we can detect overflow from the sequence
        $workerId = 4;
        $datacenterId = 4;
        $startSequence = 0xFFFFFF-20;
        $endSequence = 0xFFFFFF+20;
        $mask = 0x000000000001F000;

        $this->beConstructedWith($workerId, $datacenterId, $startSequence);

        for ($i = $startSequence; $i < $endSequence; $i++) {
            $this->nextId()->shouldBeProperlyMasked($workerId, $mask, 12);
        }

    }

    function it_generate_increasing_ids()
    {
        $this->beAnInstanceOf('LucasVscn\Snowflake\KeepLastIdWorker');
        $this->beConstructedWith(1,1);

        $lastId = 0;

        for ($i = 0; $i < 100; $i++) {
            $this->nextId()->shouldBeGreaterThan($lastId);
            $lastId = $this->lastId;
        }
    }

    function it_sleep_if_we_would_rollover_twice_in_the_same_millisecond()
    {
        $this->beAnInstanceOf('LucasVscn\Snowflake\WalkingIdWorker');
        $this->beConstructedWith(1, 1);

        $this->timestamp = array(null, 2, 2, 2, 3);

        $this->seq = 4096;
        $this->nextId();

        $this->seq = 4096;
        $this->nextId();

        $this->getSlept()->shouldBe(1);
    }

    // function it_generate_only_unique_ids()
    // {
    //     $this->beAnInstanceOf('LucasVscn\Snowflake\KeepLastIdWorker');
    //     $this->beConstructedWith(31, 31);

    //     $max = 11000;
    //     $ids = [];

    //     for ($i = 0; $i < $max; $i++) {
    //         $this->nextId()->shouldBeUnique($ids);
    //         $ids[] = $this->lastId;
    //     }

    //     unset($ids);
    // }

    function it_generate_ids_over_50_billion()
    {
        $this->beAnInstanceOf('LucasVscn\Snowflake\KeepLastIdWorker');
        $this->beConstructedWith(31, 31);

        $this->nextId()->shouldBeGreaterThan(50000000000);
    }

    function it_generate_only_unique_ids_even_when_time_goes_backwards()
    {
        $this->beAnInstanceOf('LucasVscn\Snowflake\StaticTimeWorker');
        $this->beConstructedWith(31, 31);
        $mask = -1 ^ (-1 << 12);

        $this->getSequence()->shouldBe(0);
        $this->nextId()->shouldBeProperlyMasked(0, $mask, 0);

        $this->getSequence()->shouldBe(1);
        $this->nextId()->shouldBeProperlyMasked(1, $mask, 0);

        // now time go backwards
        $this->timestamp = 0;
        $this->getSequence()->shouldBe(2); // always move forward.
        $this->shouldThrow('LucasVscn\Snowflake\InvalidSystemClockException')->duringNextId();
        $this->getSequence()->shouldBe(2);

        $this->timestamp = 1;
        $this->nextId()->shouldBeProperlyMasked(2, $mask, 0);
    }

    function it_throws_exception_if_worker_id_is_invalid()
    {
        $this->shouldThrow('\InvalidArgumentException')->during('__construct', [-1, 1]);
        $this->shouldThrow('\InvalidArgumentException')->during('__construct', [32, 1]);
    }

    function it_throws_exception_if_datacenter_id_is_invalid()
    {
        $this->shouldThrow('\InvalidArgumentException')->during('__construct', [1, -1]);
        $this->shouldThrow('\InvalidArgumentException')->during('__construct', [1, 32]);
    }

    public function getMatchers()
    {
        return [
            'beProperlyMasked' => function ($subject, $input, $mask, $shiftBits)
            {
                return (($subject & $mask) >> $shiftBits) == $input;
            },
            'beSameBits' => function ($subject, $shift, $expected)
            {
                return ($subject >> $shift) == $expected;
            },
            'beGreaterThan' => function ($subject, $val)
            {
                return $subject > $val;
            },
            'beUnique' => function ($subject, $haystack)
            {
                return ! in_array($subject, $haystack);
            }
        ];
    }
}

namespace LucasVscn\Snowflake;

class EasyTimeWorker extends IdWorker
{
    public $timestamp;
    public function getTimestamp() { return $this->timestamp; }
}

class StaticTimeWorker extends IdWorker
{
    public $timestamp = 1;
    public function getTimestamp()
    {
        return $this->timestamp + self::TWEPOC;
    }
}

class KeepLastIdWorker extends IdWorker
{
    public $lastId;
    public function nextId()
    {
        return $this->lastId = parent::nextId();
    }
}

class WalkingIdWorker extends EasyTimeWorker
{
    public $slept = 0;
    public $seq;

    public function nextSequence()
    {
        return $this->seq;
    }

    public function getTimestamp()
    {
        return next($this->timestamp);
    }

    protected function tilNextMillis($lastTimestamp)
    {
        $this->slept += 1;
        return parent::tilNextMillis($lastTimestamp);
    }

    public function getSlept()
    {
        return $this->slept;
    }
}