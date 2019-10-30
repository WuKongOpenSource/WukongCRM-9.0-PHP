<?php

namespace com;

use think\Cache;

/**
 * 伪队列，（用于导入导出轮询时排队）
 *
 * @author  Ymob
 * @date    2019-09-30
 */
class PseudoQueue
{

    /**
     * 队列名称
     *
     * @var string
     */
    protected $name = '';

    /**
     * 队列最大数量，默认0，不限数量
     *
     * @var int
     */
    protected $max = 0;

    /**
     * 最大可执行数
     *
     * @var int
     */
    protected $max_exec = 0;

    /**
     * 队列任务请求最大间隔时间，单位秒 默认600秒
     *
     * *考虑网络波动等原因不建议小于 10 秒*
     *
     * @var int
     */
    protected $overtime = 600;

    /**
     * 队列
     *
     * @var array
     */
    protected $queue = [];

    /**
     * 当前任务ID
     *
     * @var string
     */
    public $task_id = '';

    /**
     * 错误信息
     *
     * @var string
     */
    public $error = '';

    /**
     * 构造函数
     *
     * @param string $name      队列名称
     * @param int    $max_exec  最大可执行数
     */
    public function __construct($name, $max_exec)
    {
        $this->name = 'QUEUE_' . $name;
        $this->max_exec = $max_exec;
        $this->queue = Cache::get($this->name) ?: [];
    }

    /**
     * 设置当前任务ID
     *
     * @param string $task_id 队列索引
     * @return bool
     */
    public function setTaskId($task_id)
    {
        $index = $this->getIndex($task_id);
        if ($index === false) {
            return false;
        }

        $this->task_id = $task_id;
        return true;
    }

    /**
     * 设置队列最大长度
     *
     * @param int $max
     * @return void
     */
    public function setMax(int $max)
    {
        $this->max = $max;
    }

    /**
     * 队列最大长度
     *
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * 设置队列任务请求最大间隔时间 (单位：秒s)
     *
     * @param int $overtime
     * @return void
     */
    public function setOvertime(int $overtime)
    {
        $this->overtime = $overtime;
    }

    /**
     * 读取队列任务请求最大间隔时间
     *
     * @return int
     */
    public function getOvertime()
    {
        return $this->overtime;
    }

    /**
     * 生成任务ID，并加入队列
     *
     * @return string
     */
    public function makeTaskId()
    {
        do {
            $task_id = md5(time() . rand(100, 999));
        } while (in_array($task_id, array_column($this->queue, 'task_id')));

        if (!$this->enqueue($task_id)) {
            return false;
        }

        $this->task_id = $task_id;
        return $task_id;
    }

    /**
     * 入队
     *
     * @param string $$this->queue 任务ID
     * @return bool
     */
    public function enqueue($task_id)
    {
        if ($this->max > 0) {
            if (count($this->queue) >= $this->max) {
                $this->error = '队列长度达到上限';
                return false;
            }
        }
        $this->queue[] = [
            'task_id' => $task_id,
            // 上次处理时间
            'last_processing_time' => time(),
        ];
        Cache::set($this->name, $this->queue);
        return true;
    }

    /**
     * 出队
     *
     * @param string $task_id 任务ID，默认当前任务ID
     * @return bool
     */
    public function dequeue($task_id = null)
    {
        $task_id = $task_id ?: $this->task_id;
        $index = $this->getIndex($task_id);
        if ($index === false) {
            return false;
        }

        unset($this->queue[$index]);
        $this->queue = array_values($this->queue);
        Cache::set($this->name, $this->queue);
        return true;
    }

    /**
     * 当前任务是否可执行
     *
     * @param string $task_id 任务ID，默认当前任务ID
     * @return bool
     */
    public function canExec($task_id = null)
    {
        $task_id = $task_id ?: $this->task_id;

        $index = $this->getIndex($task_id);
        if ($index === false) {
            return false;
        }

        $res = false;

        for ($i = 0; $i < $this->max_exec; $i++) {
            if (!isset($this->queue[$i])) {
                break;
            }
            // 判断任务处理是否超时
            $temp_time = $this->queue[$i]['last_processing_time'] + $this->overtime;
            if (time() > $temp_time) {
                $this->dequeue($this->queue[$i]['task_id']);
                $i--;
                $index--;
                continue;
            }

            if ($index == $i) {
                $res = true;
                $this->queue[$i]['last_processing_time'] = time();
                Cache::set($this->name, $this->queue);
                break;
            }
        }

        if (!$res) {
            $this->error = '服务器繁忙，排队中...' . ($index - $this->max_exec);
        }

        return $res;
    }

    /**
     * 获取 [当前] 任务所在队列索引
     *
     * @param string $task_id 任务ID，默认当前任务ID
     * @return mixed bool | int
     */
    public function getIndex($task_id = null)
    {
        $task_id = $task_id ?: $this->task_id;

        $index = array_search($task_id, array_column($this->queue, 'task_id'));
        if ($index === false) {
            $this->error = '队列中不存在该任务';
        }

        return $index;
    }

    /**
     *
     */
    /**
     * 任务缓存数据
     *
     * @param string    $key    缓存键
     * @param mixed     $val    缓存值，不传改参数为获取值
     * @return mixed|void
     * @author Ymob
     * @datetime 2019-10-22 13:52:19
     */
    public function cache($key, $val = null)
    {
        if (!$this->task_id) {
            $this->error = '任务ID不存在';
            return false;
        }

        $index = $this->getIndex($this->task_id);

        if ($val === null) {
            return $this->queue[$index]['cache'][$key];
        } else {
            $this->queue[$index]['cache'][$key] = $val;
            Cache::set($this->name, $this->queue);
        }

    }
}
