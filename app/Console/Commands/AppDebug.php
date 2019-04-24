<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppDebug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appdebug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $x = new Unit(2);
        $y = new Unit(2);
        $z = new Unit(2);
        $inputs = [[0,0],[0,1],[1,0],[1,1]];
        $outputs = [0, 1, 1, 0];
        for ($i = 0; $i < 80; $i++) {
            $errors = [];
            foreach ($inputs as $k => $input) {
                list($e, $o) = $this->fit($input, $outputs[$k], $x, $y, $z, 0.9);
                $errors[] = sprintf('%.4f[%.2f]', $e, $o);
            }
            $this->info(implode(' ', $errors));
        }
    }

    private function fit($input, $output, $x, $y, $z, $lr = 0.001)
    {
        $ox = $x->run($input);
        $oy = $y->run($input);
        $iz = [$ox, $oy];
        $oz = $z->run($iz);
        $error = pow($oz - $output, 2) / 2;
        $dedr = $oz - $output;
        $diz = $z->derivative();
        $xd = $x->derivative();
        for ($i = 0; $i < 2; $i++) {
            $x->weight[$i] = $x->weight[$i] - $lr * -1 * $xd[$i] * $diz[0] * $dedr;
        }
        $yd = $y->derivative();
        for ($i = 0; $i < 2; $i++) {
            $y->weight[$i] = $y->weight[$i] - $lr * -1 * $yd[$i] * $diz[1] * $dedr;
        }
        for ($i = 0; $i < 2; $i++) {
            $z->weight[$i] = $z->weight[$i] - $lr * -1 * $diz[$i] * $dedr;
        }
        return [$error, $oz];
    }
}
