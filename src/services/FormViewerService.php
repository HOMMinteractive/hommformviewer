<?php
/**
 * HOMMFormViewer plugin for Craft CMS 5.x
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
        $head = ['id', 'date'];
        $body = [];
        $items = $this->query()->where(['formId' => $form])->all();

        foreach ($items as $key => $item) {
            $payload = str_replace(["\r\n","\t"], [' ', ' '], $item['payload']);
            $payload = json_decode($payload, true);

            unset($payload['recaptcha_response']);
            $head = array_merge($head, array_keys($payload ?? []));
        }
        $head = array_unique($head);

        foreach ($items as $key => $item) {
            $payload = str_replace(["\r\n","\t"], [' ', ' '], $item['payload']);
            $payload = json_decode($payload, true);

            $row['id'] = $item['id'];
            $row['date'] = $item['date'];

            foreach ($head as $i) {
                if (in_array($i, ['id', 'date'])) {
                    continue;
                }

                if (json_validate(str_replace("&quot;", '"', $payload[$i])) && isset($payload[$i])) {
                    $row[$i] = json_decode(str_replace("&quot;", '"', $payload[$i]), true);
                } else {
                    $row[$i] = $payload[$i] ?? '';
                }
            }
            
            $body[] = $row;
        }
        
        return [$head, ...$body];
    }

    public function deleteData(string $form): bool
    {
        $table = HOMMFormViewer::$plugin->getSettings()->table;

        return (new Query())
            ->createCommand()
            ->delete($table, ['formId' => $form])
            ->execute();
    }
}
