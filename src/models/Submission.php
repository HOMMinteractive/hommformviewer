<?php

namespace homm\hommform\models;

use craft\base\Model;
use DateTime;

/**
 * Data container for an incoming form submission.
 */
class Submission extends Model
{
    public string $formId;
    public string $receivers;
    public ?string $subject = null;
    public ?string $replyto = null;
    public ?string $recaptchaResponse = null;
    public string $ip;
    public array $payload = [];
    public ?string $confirmation = null;
    public DateTime $dateCreated;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['formId', 'receivers', 'ip', 'dateCreated'], 'required'],
            [['subject', 'replyto', 'recaptchaResponse', 'confirmation'], 'string'],
            ['payload', 'safe'],
        ];
    }
}
