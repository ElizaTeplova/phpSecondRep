<?php

declare(strict_types=1);

namespace App\Config;

class RequestParams
{
    // private string $date1 = "2023-01-01";

    private string $date1;
    private string $date2;
    private array $fields;
    private string $source;

    public function __construct(
        string $date1 = '',
        string $date2 = '',
        array $fields = [],
        string $source = "visits"
    ) {
        $this->date1 = $date1 ?: date('Y-m-d', strtotime('-1 year'));
        $this->date2 = $date2 ?: date('Y-m-d', strtotime('yesterday'));
        $this->source = $source;
        if (!count($fields)) {
            $this->fields = [
                'ym:s:visitID',             # Идентификатор визита
                'ym:s:clientID',            # Анонимный идентификатор пользователя в браузере (first-party cookies)
                'ym:s:date',                # Дата визита
                'ym:s:watchIDs',            # Просмотры, которые были в данном визите. Ограничение массива — 500 просмотров
                'ym:s:startURL',            # Страница входа
                'ym:s:endURL',              # Страница выхода
                'ym:s:pageViews',           # Глубина просмотра (детально)
                'ym:s:visitDuration',       # Время на сайте (детально)
                'ym:s:regionCountry',       # ID страны
                'ym:s:regionCity',          # ID города
                'ym:s:goalsID',             # Номера целей, достигнутых за данный визит
                'ym:s:goalsSerialNumber',   # Порядковые номера достижений цели с конкретным идентификатором
                'ym:s:referer',             # Реферер
                'ym:s:deviceCategory',      # Тип устройства. Возможные значения: 1 — десктоп, 2 — мобильные телефоны, 3 — планшеты, 4 — TV
                'ym:s:operatingSystem',     # Операционная система (детально)
                'ym:s:browser',
            ];
        }
    }

    public function getParams(): array
    {
        return [
            'date1' => $this->date1,
            'date2' => $this->date2,
            'fields' => implode(',', $this->fields),
            'source' => $this->source
        ];
    }
}
