<?php

    namespace Coco\queue;

class Launcher
{
    public string $command;
    public string $scriptName;

    public int $times = 1;

    public function __construct(public string $scriptPath, public string $phpBin = 'php')
    {
        if (!is_file($scriptPath)) {
            throw new \Exception($scriptPath . ' 不存在');
        }

        if (!is_executable($scriptPath)) {
            throw new \Exception($scriptPath . ' 不可执行');
        }

        $this->scriptPath = realpath($scriptPath);
        ;

        $this->scriptName = pathinfo($this->scriptPath, PATHINFO_FILENAME);
    }

    public function setTimes(string $times): static
    {
        $this->times = $times;

        return $this;
    }

    public function getLanuchCommand(): string
    {
        $this->chdir();

        $arr = [
            'nohup',
            $this->phpBin,
            $this->scriptPath,
            '> /dev/null 2>&1  &',
        ];

        $command = implode(' ', $arr);

        return $command;
    }

    public function getStopCommand(): string
    {
        $arr = [
            'pkill',
            '-f',
            '"' . $this->scriptPath . '"',
        ];

        return implode(' ', $arr);
    }

    public function launch(): void
    {
        for ($i = 0; $i < $this->times; $i++) {
            $command = $this->getLanuchCommand();
            exec($command, $output, $status);

            if ($status === 0) {
                $msg = "执行成功: " . $command . PHP_EOL;
            } else {
                $msg = "执行失败: " . $command . PHP_EOL;
                $msg .= json_encode($output, 256) . PHP_EOL;
            }

            echo $msg;
        }

        echo '启动完成,当前启动:' . $this->times . ',一共启动:' . $this->getCount();
    }

    public function stop(): void
    {
        $count = $this->getCount();
        if ($count) {
            $command = $this->getStopCommand();

            exec($command, $output, $status);

            if ($status === 0) {
                $msg = "执行成功: " . $command . PHP_EOL;
                $msg .= '共:' . $count . PHP_EOL;
            } else {
                $msg = "执行失败: " . $command . PHP_EOL;
                $msg .= json_encode($output, 256) . PHP_EOL;
            }

            echo $msg;
        } else {
            echo '没有启动的任务';
        }
    }

    public function getCount(): ?int
    {
        return count($this->getProcessList());
    }

    public function getProcessList(): array
    {
        $arr = [
            'ps aux | grep',
            '"' . $this->scriptPath . '"',
        ];

        $command = implode(' ', $arr);

        exec($command, $output, $status);

        $result = [];
        foreach ($output as $k => $v) {
            if (!str_contains($v, 'grep')) {
                $result[] = $v;
            }
        }

        return $result;
    }

    protected function chdir(): void
    {
        chdir(dirname($this->scriptPath));
    }
}
