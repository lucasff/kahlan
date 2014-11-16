<?php
namespace spec\kahlan\reporter\coverage;

use kahlan\reporter\coverage\Collector;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Coveralls;
use spec\fixture\reporter\coverage\NoEmptyLine;
use spec\fixture\reporter\coverage\ExtraEmptyLine;

describe("Coveralls", function() {

    describe("::export()", function() {

        it("exports the coverage of a file with no extra end line", function() {

            $path = 'spec/fixture/reporter/coverage/NoEmptyLine.php';

            $collector = new Collector([
                'driver'    => new Xdebug(),
                'path'      => $path
            ]);

            $code = new NoEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $json = Coveralls::export([
                'collector'      => $collector,
                'service_name'   => 'kahlan-ci',
                'service_job_id' => '123',
                'repo_token'     => 'ABC'
            ]);

            $actual = json_decode($json, true);
            unset($actual['run_at']);
            expect($actual['service_name'])->toBe('kahlan-ci');
            expect($actual['service_job_id'])->toBe('123');
            expect($actual['repo_token'])->toBe('ABC');

            $coverage = $actual['source_files'][0];
            expect($coverage['name'])->toBe($path);
            expect($coverage['source'])->toBe(file_get_contents($path));
            expect($coverage['coverage'])->toHaveLength(15);
            expect(array_filter($coverage['coverage']))->toHaveLength(2);

            expect(array_filter($coverage['coverage'], function($value){
                return $value === 0;
            }))->toHaveLength(2);

            expect(array_filter($coverage['coverage'], function($value){
                return $value === null;
            }))->toHaveLength(11);

        });

        it("exports the coverage of a file with an extra line at the end", function() {

            $path = 'spec/fixture/reporter/coverage/ExtraEmptyLine.php';

            $collector = new Collector([
                'driver'    => new Xdebug(),
                'path'      => $path
            ]);

            $code = new ExtraEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $json = Coveralls::export([
                'collector'      => $collector,
                'service_name'   => 'kahlan-ci',
                'service_job_id' => '123',
                'repo_token'     => 'ABC'
            ]);

            $actual = json_decode($json, true);
            unset($actual['run_at']);
            expect($actual['service_name'])->toBe('kahlan-ci');
            expect($actual['service_job_id'])->toBe('123');
            expect($actual['repo_token'])->toBe('ABC');

            $coverage = $actual['source_files'][0];
            expect($coverage['name'])->toBe($path);
            expect($coverage['source'])->toBe(file_get_contents($path));
            expect($coverage['coverage'])->toHaveLength(16);

            expect(array_filter($coverage['coverage'], function($value){
                return $value === 0;
            }))->toHaveLength(2);

            expect(array_filter($coverage['coverage'], function($value){
                return $value === null;
            }))->toHaveLength(12);

        });

    });


    describe("::write()", function() {

        beforeEach(function() {
            $this->output = tempnam("/tmp", "KAHLAN");
        });

        afterEach(function() {
            unlink($this->output);
        });

        it("writes the coverage to a file", function() {

            $path = 'spec/fixture/reporter/coverage/ExtraEmptyLine.php';

            $collector = new Collector([
                'driver'    => new Xdebug(),
                'path'      => $path
            ]);

            $code = new ExtraEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $success = Coveralls::write([
                'collector'      => $collector,
                'file'           => $this->output,
                'service_name'   => 'kahlan-ci',
                'service_job_id' => '123',
                'repo_token'     => 'ABC'
            ]);

            expect($success)->toBe(545);

            $json = file_get_contents($this->output);
            $actual = json_decode($json, true);
            unset($actual['run_at']);
            expect($actual['service_name'])->toBe('kahlan-ci');
            expect($actual['service_job_id'])->toBe('123');
            expect($actual['repo_token'])->toBe('ABC');

            $coverage = $actual['source_files'][0];
            expect($coverage['name'])->toBe($path);
            expect($coverage['source'])->toBe(file_get_contents($path));
            expect($coverage['coverage'])->toHaveLength(16);

        });

    });

});