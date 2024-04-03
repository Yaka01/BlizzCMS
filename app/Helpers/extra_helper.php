<?php

use App\Libraries\Multilanguage;

/**
 * BlizzCMS
 *
 * @author WoW-CMS
 * @copyright Copyright (c) 2019 - 2023, WoW-CMS (https://wow-cms.com)
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Change a specific datetime in a localized pattern
 *
 * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime
 *
 * @param string $date
 * @param string|null $pattern
 * @param string|null $timezone
 * @return string
 */
function localeDate($date, $pattern = null, $timezone = null)
{
    $pattern ??= lang("General.datetime_pattern");
    $timezone ??= configItem("time_reference");

    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '';
    }

    $multilanguage = new Multilanguage();

    $dateTime = new \DateTime($date);
    $formatter = new \IntlDateFormatter($multilanguage->currentLanguage('locale'), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, $timezone, \IntlDateFormatter::GREGORIAN);

    $formatter->setPattern($pattern);

    return $formatter->format($dateTime);
}
