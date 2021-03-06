<?php

namespace Biz\Task\Dao\Impl;

use Biz\Task\Dao\TaskDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class TaskDaoImpl extends GeneralDaoImpl implements TaskDao
{
    protected $table = 'course_task';

    public function deleteByCategoryId($categoryId)
    {
        return $this->db()->delete($this->table(), array('categoryId' => $categoryId));
    }

    public function findByCourseId($courseId)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE courseId = ? ORDER  BY seq";

        return $this->db()->fetchAll($sql, array($courseId)) ?: array();
    }

    public function findByCourseIds($courseIds)
    {
        return $this->findInField('courseId', $courseIds);
    }

    public function findByActivityIds($activityIds)
    {
        return $this->findInField('activityId', $activityIds);
    }

    public function findByCourseSetId($courseSetId)
    {
        return $this->findByFields(array(
            'fromCourseSetId' => $courseSetId,
        ));
    }

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function findByCourseIdAndCategoryId($courseId, $categoryId)
    {
        return $this->findByFields(array('courseId' => $courseId, 'categoryId' => $categoryId));
    }

    public function getMaxSeqByCourseId($courseId)
    {
        $sql = "SELECT MAX(seq) FROM {$this->table()} WHERE courseId = ? ";

        return $this->db()->fetchColumn($sql, array($courseId)) ?: 0;
    }

    public function getNumberSeqByCourseId($courseId)
    {
        $sql = "SELECT MAX(number) FROM {$this->table()} WHERE courseId = ? ";

        return $this->db()->fetchColumn($sql, array($courseId)) ?: 0;
    }

    public function getMinSeqByCourseId($courseId)
    {
        $sql = "SELECT MIN(seq) FROM {$this->table()} WHERE courseId = ? ";

        return $this->db()->fetchColumn($sql, array($courseId)) ?: 0;
    }

    public function getNextTaskByCourseIdAndSeq($courseId, $seq)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE seq > ? and courseId = ?  ORDER BY seq ASC LIMIT 1 ";

        return $this->db()->fetchAssoc($sql, array($seq, $courseId));
    }

    public function getPreTaskByCourseIdAndSeq($courseId, $seq)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE seq < ? and courseId = ?  ORDER BY seq DESC LIMIT 1 ";

        return $this->db()->fetchAssoc($sql, array($seq, $courseId));
    }

    public function findByChapterId($chapterId)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE categoryId = ? ORDER BY seq ASC ";

        return $this->db()->fetchAll($sql, array($chapterId)) ?: array();
    }

    public function getByChapterIdAndMode($chapterId, $mode)
    {
        $sql = "SELECT * FROM {$this->table()}  WHERE `categoryId`= ? AND `mode` = ? LIMIT 1";

        return $this->db()->fetchAssoc($sql, array($chapterId, $mode));
    }

    public function getByCourseIdAndSeq($courseId, $sql)
    {
        return $this->getByFields(array('courseId' => $courseId, 'seq' => $sql));
    }

    /**
     * 统计当前时间以后每天的直播次数.
     *
     * @param  $limit
     *
     * @return array <string, int|string>
     */
    public function findFutureLiveDates($limit)
    {
        $time = time();

        $sql = "SELECT count( id) as count, from_unixtime(startTime,'%Y-%m-%d') as date FROM `{$this->table()}` WHERE  `type`= 'live' AND status='published' AND startTime >= {$time} group by date order by date ASC limit 0, {$limit}";

        return $this->db()->fetchAll($sql);
    }

    /**
     * 返回过去直播过的教学计划ID.
     *
     * @return array<int>
     */
    public function findPastLivedCourseSetIds()
    {
        $time = time();
        $sql
        = "SELECT fromCourseSetId, max(startTime) as startTime
                 FROM {$this->table()}
                 WHERE endTime < {$time} AND status='published' AND type = 'live'
                 GROUP BY fromCourseSetId
                 ORDER BY startTime DESC
                 ";

        return $this->db()->fetchAll($sql);
    }

    public function getTaskByCourseIdAndActivityId($courseId, $activityId)
    {
        return $this->getByFields(array('courseId' => $courseId, 'activityId' => $activityId));
    }

    public function findByCourseIdAndIsFree($courseId, $isFree)
    {
        return $this->findByFields(array('courseId' => $courseId, 'isFree' => $isFree));
    }

    public function findByCopyIdAndLockedCourseIds($copyId, $courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $parmaters = array_merge(array($copyId), $courseIds);

        $sql = "SELECT * FROM {$this->table()} WHERE copyId= ? AND courseId IN ({$marks})";

        return $this->db()->fetchAll($sql, $parmaters) ?: array();
    }

    public function sumCourseSetLearnedTimeByCourseSetId($courseSetId)
    {
        $sql = "select sum(`time`) from `course_task_result` where `courseTaskId` in (SELECT id FROM {$this->table()}  WHERE `fromCourseSetId`= ?)";

        return $this->db()->fetchColumn($sql, array($courseSetId));
    }

    public function analysisTaskDataByTime($startTime, $endTime)
    {
        $conditions = array(
            'createdTime_GE' => $startTime,
            'createdTime_LT' => $endTime,
        );

        $builder = $this->createQueryBuilder($conditions)
            ->select("count(id) AS count, from_unixtime(createdTime, '%Y-%m-%d') AS date")
            ->from($this->table, $this->table)
            ->groupBy('date')
            ->addOrderBy('date', 'asc');

        return $builder->execute()->fetchAll();
    }

    public function declares()
    {
        return array(
            'timestamps' => array(
                'createdTime',
                'updatedTime',
            ),
            'orderbys' => array(
                'seq',
                'startTime',
                'createdTime',
                'updatedTime',
            ),
            'conditions' => array(
                'id = :id',
                'id IN ( :ids )',
                'id NOT IN (:excludeIds)',
                'courseId = :courseId',
                'courseId IN ( :courseIds )',
                'title LIKE :titleLike',
                'fromCourseSetId = :fromCourseSetId',
                'fromCourseSetId IN (:fromCourseSetIds)',
                'status =:status',
                'type = :type',
                'isFree =:isFree',
                'type IN ( :types )',
                'seq >= :seq_GE',
                'seq > :seq_GT',
                'seq < :seq_LT',
                'startTime >= :startTime_GE',
                'createdTime >= :createdTime_GE',
                'createdTime <= :createdTime_LE',
                'startTime > :startTime_GT',
                'startTime <= :startTime_LE',
                'updatedTime >= :updatedTime_GE',
                'updatedTime <= :updatedTime_LE',
                'endTime > :endTime_GT',
                'endTime < :endTime_LT',
                'endTime <= :endTime_GE',
                'categoryId = :categoryId',
                'activityId = :activityId',
                'mode = :mode',
            ),
        );
    }
}
