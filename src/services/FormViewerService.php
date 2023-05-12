<?php
/**
 * HOMMFormViewer plugin for Craft CMS 4.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommformviewer\services;

use craft\db\Query;
use craft\base\Component;
use homm\hommformviewer\HOMMFormViewer;

/**
 * @author    Domenik Hofer
 * @package   HOMMFormViewer
 * @since     1.0.0
 */
class FormViewerService extends Component
{
    private function query(): Query
    {
        $table = HOMMFormViewer::$plugin->getSettings()->table;

        return (new Query())
            ->select(['*'])
            ->from([$table]);
    }

    // Public Methods
    // =========================================================================

    /**
     * Get all form names.
     *
     * @return string[]
     */
    public function getForms(): array
    {
        $result = $this->query()->groupBy('formId')->all();

        return array_column($result, 'formId');
    }

    public function getData(string $form): array
    {
        $head = [];
        $body = [];
        $items = $this->query()->where(['formId' => $form])->all();

        foreach ($items as $key => $item) {
            $payload = json_decode($item['payload'], true);
            unset($payload['recaptcha_response']);
            $head = array_merge($head, array_keys($payload));
        }
        $head = array_unique($head);

        foreach ($items as $key => $item) {
            $payload = json_decode($item['payload'], true);

            $row = [];
            foreach ($head as $i) {
                $row[$i] = $payload[$i] ?? '';
            }
            $body[] = $row;
        }

        return mb_convert_encoding([$head, ...$body], 'ISO-8859-1', 'UTF-8');
    }
}
