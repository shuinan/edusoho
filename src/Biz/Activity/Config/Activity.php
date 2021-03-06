<?php

namespace Biz\Activity\Config;

use Biz\Activity\Listener\Listener;
use Codeages\Biz\Framework\Context\Biz;
use AppBundle\Common\Exception\UnexpectedValueException;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use Codeages\Biz\Framework\Service\Exception\AccessDeniedException;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;

abstract class Activity
{
    private $biz;

    public function create($fields)
    {
    }

    /**
     * @param int   $targetId
     * @param array $fields   fields to update
     * @param array $activity existed activity
     */
    public function update($targetId, &$fields, $activity)
    {
    }

    public function delete($targetId)
    {
    }

    /**
     * 实现Activity的复制，这里仅需要处理Activity的附属信息.
     *
     * @param array $activity 要复制的Activity
     * @param array $config   : newLiveroom => true/false // 是否新建直播教室（对于直播任务）
     *
     * @return mixed
     */
    public function copy($activity, $config = array())
    {
        return null;
    }

    /**
     * 实现Activity附属信息的同步.
     *
     * @param array $sourceActivity 源activity
     * @param array $activity       目标activity
     *
     * @return mixed
     */
    public function sync($sourceActivity, $activity)
    {
        return null;
    }

    public function allowTaskAutoStart($activity)
    {
        return true;
    }

    public function isFinished($id)
    {
        return false;
    }

    public function get($targetId)
    {
        return array();
    }

    public function find($targetIds)
    {
        return array();
    }

    final public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    /**
     * @return mixed
     */
    abstract protected function registerListeners();

    /**
     * @param string $eventName
     *
     * @return Listener
     */
    final public function getListener($eventName)
    {
        $map = $this->registerListeners();
        if (empty($map) || !isset($map[$eventName])) {
            return null;
        }
        $reflection = new \ReflectionClass($map[$eventName]);
        $listener = $reflection->newInstanceArgs(array($this->getBiz()));

        if (!$listener instanceof Listener) {
            throw new UnexpectedValueException('listener class must be Listener Derived Class');
        }

        return $listener;
    }

    protected function createNotFoundException($message = '')
    {
        return new NotFoundException($message);
    }

    protected function createAccessDeniedException($message = '')
    {
        return new AccessDeniedException($message);
    }

    protected function createInvalidArgumentException($message = '')
    {
        return new InvalidArgumentException($message);
    }

    /**
     * @return Biz
     */
    final protected function getBiz()
    {
        return $this->biz;
    }
}
