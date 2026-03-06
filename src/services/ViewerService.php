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
     * Return a list of form IDs for which submissions exist.
     *
     * @return string[]
     */
    public function getForms(): array
    {
        $result = $this->query()->groupBy('formId')->all();

        return array_column($result, 'formId');
    }

    /**
     * Sanitize a payload stored in the database.  This method strips control
     * characters, removes known internal fields and attempts to JSON‑decode
     * any strings that look like JSON.
     */
    private function sanitizePayload(string $raw): array
    {
        $payload = str_replace(["\r\n", "\t"], [' ', ' '], $raw);
        $payload = json_decode($payload, true) ?: [];

        unset(
            $payload['g-recaptcha-response'],
            $payload['CRAFT_CSRF_TOKEN'],
            $payload['redirect']
        );

        foreach ($payload as $k => $v) {
            if (is_string($v) && json_validate($v)) {
                $payload[$k] = json_decode($v, true);
            }
        }

        return $payload;
    }

    /**
     * Retrieve all submissions for a given form as a two‑dimensional table.
     * The first element of the returned array is the header row.
     *
     * @param string $form
     * @return array[]
     */
    public function entries(string $form): array
    {
        $head = ['id', 'dateCreated'];
        $body = [];
        $items = $this->query()->where(['formId' => $form])->all();

        // build header keys
        foreach ($items as $item) {
            $payload = $this->sanitizePayload($item['payload']);
            $head = array_merge($head, array_keys($payload));
        }
        $head = array_unique($head);

        foreach ($items as $item) {
            $payload = $this->sanitizePayload($item['payload']);

            $row['id'] = $item['id'];
            $row['dateCreated'] = $item['dateCreated'];

            foreach ($head as $i) {
                if (in_array($i, ['id', 'dateCreated'], true)) {
                    continue;
                }

                $row[$i] = $payload[$i] ?? null;
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

    /**
     * Return a CSV string containing all submissions for the given form.
     */
    public function exportCsv(string $form): string
    {
        $entries = $this->entries($form);
        $context = fopen('php://temp', 'r+');

        if (! empty($entries)) {
            $headers = array_shift($entries);
            fputcsv($context, $headers);

            foreach ($entries as $item) {
                $rowData = [];
                foreach ($headers as $header) {
                    $value = $item[$header] ?? '';
                    if (is_array($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                    $rowData[] = $value;
                }
                fputcsv($context, $rowData);
            }
        }

        rewind($context);
        $csv = stream_get_contents($context);
        fclose($context);

        return $csv;
    }
}
