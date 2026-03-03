<?php
/**
 * HOMMForm plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2026 HOMM interactive
 */

namespace homm\hommform\services;

use craft\db\Query;
use craft\base\Component;

/**
 * @author    Benjamin Ammann
 * @package   HOMMForm
 * @since     4.0.0
 */
class ViewerService extends Component
{
    private function query(): Query
    {
        return (new Query())
            ->select(['*'])
            ->from(['{{%homm_form_submissions}}']);
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

    public function entries(string $form): array
    {
        $head = ['id', 'dateCreated'];
        $body = [];
        $items = $this->query()->where(['formId' => $form])->all();

        foreach ($items as $key => $item) {
            $payload = str_replace(["\r\n","\t"], [' ', ' '], $item['payload']);
            $payload = json_decode($payload, true);

            unset(
                $payload['g-recaptcha-response'],
                $payload['CRAFT_CSRF_TOKEN'],
                $payload['redirect']
            );

            $head = array_merge($head, array_keys($payload ?? []));
        }
        $head = array_unique($head);

        foreach ($items as $item) {
            $payload = str_replace(["\r\n","\t"], [' ', ' '], $item['payload']);
            $payload = json_decode($payload, true);

            $row['id'] = $item['id'];
            $row['dateCreated'] = $item['dateCreated'];

            foreach ($head as $i) {
                if (in_array($i, ['id', 'dateCreated'])) {
                    continue;
                }

                if (! isset($payload[$i])) {
                    $row[$i] = null;
                    continue;
                }

                if (is_array($payload[$i])) {
                    $row[$i] = $payload[$i];
                    continue;
                }

                $payload[$i] = str_replace("&quot;", '"', $payload[$i]);
                if (json_validate($payload[$i])) {
                    $row[$i] = json_decode($payload[$i], true);
                    continue;
                }

                $row[$i] = $payload[$i] ?? '';
            }

            $body[] = $row;
        }

        return [$head, ...$body];
    }

    public function delete(string $form): bool
    {
        return (new Query())
            ->createCommand()
            ->delete('{{%homm_form_submissions}}', ['formId' => $form])
            ->execute();
    }
}
