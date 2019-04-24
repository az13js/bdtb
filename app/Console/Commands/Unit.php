<?php

namespace App\Console\Commands;

class Unit
{
    public $weight = [];

    public $input = 0;

    public $lastR = null;

    public $lastInput = [];

    public function __construct(int $input)
    {
        $this->input = $input;
        for ($i = 0; $i < $input; $i++) {
            $this->weight[$i] = mt_rand() / mt_getrandmax();// 0 ~ 1
            $this->weight[$i] *= 2;// 0 ~ 2
            $this->weight[$i] -= 1;// -1 ~ 1
        }
    }

    public function run(array &$input): float
    {
        $s = 0;
        for ($i = 0; $i < $this->input; $i++) {
            $s += pow($this->weight[$i] - $input[$i], 2);
            $this->lastInput[$i] = $input[$i];
        }
        return $this->lastR = sqrt($s);
    }

    /**
     * 输出对输入的偏导数
     *
     * @return array
     */
    public function derivative(): array
    {
        $result = [];
        for ($i = 0; $i < $this->input; $i++) {
            $result[$i] = 0 == $this->lastR ? 1 : ($this->lastInput[$i] - $this->weight[$i]) / $this->lastR;
        }
        return $result;
    }
}
