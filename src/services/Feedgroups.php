<?php
/**
 * Refeed Me plugin for Craft CMS 3.x
 *
 * Refeed Me is a Craft CMS plugin building on the first-party Feed Me plugin. It let's you manage feeds as groups that can be run in batches.
 *
 * @link      https://github.com/kristiansp
 * @copyright Copyright (c) 2020 Kristian S. P.
 */

namespace kristiansp\refeedme\services;

use kristiansp\refeedme\RefeedMe;

use Craft;
use craft\base\Component;
use craft\db\Query;

use kristiansp\refeedme\models\RefeedModel;
use kristiansp\refeedme\records\RefeedRecord;

use craft\helpers\Json;

/**
 * @author    Kristian S. P.
 * @package   RefeedMe
 * @since     0.9.0-dev
 */
class Feedgroups extends Component
{
    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
    public function getFeedGroups($orderBy = null)
    {
        $query = $this->_getQuery();

        if ($orderBy) {
            $query->orderBy($orderBy);
        }

        $results = $query->all();

        foreach ($results as $key => $result) {
            $results[$key] = $this->_createModelFromRecord($result);
        }

        return $results;
    }

    public function getFeedGroupById($id)
    {
        $query = $this->_getQuery();
        $query->where(['id' => $id]);

        $result = $query->one();

        return $this->_createModelFromRecord($result);
    }

    public function saveFeedgroup(RefeedModel $model, bool $runValidation = true): bool
    {
        $isNewModel = !$model->id;

        if ($runValidation && !$model->validate()) {
            Craft::info('Feed group not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewModel) {
            $record = new RefeedRecord();
        } else {
            $record = RefeedRecord::findOne($model->id);

            if (!$record) {
                throw new FeedException(Craft::t('refeed-me', 'No feed group exists with the ID “{id}”', ['id' => $model->id]));
            }
        }


        $record->name = $model->name;
        $record->caching = $model->caching;
        $record->siteId = $model->siteId ?? Craft::$app->sites->currentSite;

        if ($model->feeds) {
            $record->setAttribute('feeds', json_encode($model->feeds));
        }

        if ($model->fieldMapping) {
            $record->setAttribute('fieldMapping', json_encode($model->fieldMapping));
        }

        $record->save(false);

        if (!$model->id) {
            $model->id = $record->id;
            $model->fieldMapping = $record->fieldMapping;
        }

        return true;
    }

    // Private Methods
    // =========================================================================

    private function _getQuery()
    {
        return RefeedRecord::find()
            ->select([
                'id',
                'name',
                'feeds',
                'fieldMapping',
                'caching',
                'siteId',
                'dateCreated',
                'dateUpdated',
                'uid',
            ]);
    }

    private function _createModelFromRecord(RefeedRecord $record = null)
    {
        if (!$record) {
            return null;
        }

        $attributes = $record->toArray();

        $attributes['feeds'] = Json::decode($attributes['feeds']);

        return new RefeedModel($attributes);
    }

    private function _getRefeedRecordById(int $feedGroupId = null): RefeedRecord
    {
        if ($feedGroupId !== null) {
            $feedRecord = RefeedRecord::findOne(['id' => $feedGroupId]);

            if (!$feedRecord) {
                throw new Exception(Craft::t('refeed-me', 'No feed exists with the ID “{id}”.', ['id' => $feedGroupId]));
            }
        } else {
            $feedRecord = new RefeedRecord();
        }

        return $feedRecord;
    }
}
