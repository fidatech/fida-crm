<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class BladeCompilationTest extends TestCase
{
    public function test_every_blade_view_compiles_to_valid_php(): void
    {
        $blade = app('blade.compiler');
        $files = \Illuminate\Support\Facades\File::allFiles(resource_path('views'));
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $compiled = $blade->compileString($file->getContents());
            $tmp = tempnam(sys_get_temp_dir(), 'fida-blade-');
            $this->assertNotFalse($tmp);
            try {
                file_put_contents($tmp, $compiled);
                $process = new Process([PHP_BINARY, '-l', $tmp]);
                $process->run();
                $this->assertTrue($process->isSuccessful(), $file->getRelativePathname().' failed: '.$process->getErrorOutput().$process->getOutput());
            } finally {
                @unlink($tmp);
            }
        }
    }
}
