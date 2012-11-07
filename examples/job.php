<?php

class HelloJob implements \Kue\Job
{
    function run()
    {
        echo "Hello world\n";
        sleep(10);
        echo "Ready\n";
    }
}

class FooJob implements \Kue\Job
{
    function run()
    {
        echo time(), " Foo\n";
    }
}
