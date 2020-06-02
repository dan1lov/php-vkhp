# php-vkhp

VK Helper — библиотека, упрощающая работу с некоторыми функциями VK API.
С её помощью вы можете легко создавать кнопки/клавиатуру без лишних движений, для дальнейшего прикрепления их к отправляемому сообщению.

Также, не маловажной функцией является возможность за один раз отправить сообщение более чем 100 получателям. Не нужно создавать какие-либо циклы, чтобы по-этапно отправить нужное сообщение каждому пользователю. Просто укажите всех пользователей, которым нужно отправить сообщение, а VKHP это сделает за вас.

Текущая версия VK API — `5.107`, испольуемая по умолчанию в каждом запросе, в котором явно не указана версия.
## Требования
* PHP >= 7.2

## Установка
Вариант установки через composer:
```
composer require dan1lov/php-vkhp
```

Вторым, более простым, вариантом является просто перенос файла `src/VKHP_onefile.php` в папку с вашим проектом и последующим его подключением:
```php
require 'path/to/vkhp/VKHP_onefile.php';
```

## Примеры использования
### Готовые шаблоны
Примеры ботов, построенных с использованием `VKHP`, можно найти в [dan1lov/vk-boilerplate-bot](https://github.com/dan1lov/vk-boilerplate-bot)
### Отправка сообщения
```php
use VKHP\Method as VKHPM;

$access_token = 'your_access_token_string';
$user_id = 807533;
$message = 'Привет, дружище!';

VKHPM::messagesSend($access_token, [
    'user_ids' => $user_id,
    'message' => $message,
    'random_id' => 0
]);
```

### Сообщение с клавиатурой
```php
use VKHP\Method as VKHPM;
use VKHP\Generator as VKHPG;

// для установки типа клавиатуры используется второй аргумент
// KM_ONETIME - одноразовая, KM_INLINE - инлайн
$keyboard = VKHPG::keyboard([[
    //            текст              цвет          payload (полезная нагрузка)
    VKHPG::button('Название кнопки', VKHPG::BLUE,  [ 'command' => 'start' ]),
    VKHPG::button('Вторая кнопка',   VKHPG::GREEN, [ 'second_btn' => true ]),
]], VKHPG::KM_ONETIME);

VKHPM::messagesSend($access_token, [
    'user_ids' => $user_id,
    'message' => $message,
    'keyboard' => $keyboard
    'random_id' => 0
]);
```

### Загрузка медиа
```php
use VKHP\Method as VKHPM;

$files1 = [ 'path/to/image.png' ];
$files2 = [ 'path/to/document1.txt', 'path/to/doc2.pdf' ];

$media1 = VKHPM::uploadMessagesPhoto($access_token, $files1, [ 'peer_id' => 807533 ]);
// array('photo123_321')

$media2 = VKHPM::uploadMessagesDoc($access_token, $files2, [ 'peer_id' => 807533, 'type' => 'doc' ]);
// array('doc123_321', 'doc123_322')
```

### Запрос к VK API
```php
use VKHP\Method as VKHPM;

$query = VKHPM::make($access_token, 'users.get', [
    'user_ids' => 807533,
    'fields' => 'screen_name'
]);
```

### Временные файлы
```php
use VKHP\Scenarios as VKHPTemp;

$user_id = 807533;
$temp_folder = 'path/to/tmp/folder';

// проверка на существование файла
$exist = VKHPTemp::check($temp_folder, $user_id);
// true, false

if ($exist) {
    $temp = new VKHPTemp($temp_folder, $user_id);
    // или $temp = VKHPTemp::check($temp_folder, $user_id, true);

    $temp->command = 'buy_chickens';
    $temp->amount  = 100500;

    // сохранение в файл "{$temp_folder}/file_id{$user_id}.json"
    $temp->save();

    echo "Command: {$temp->command}, Amount: {$temp->amount}";
    // Command: buy_chickens, Amount: 100500
}

// удаление временного файла
$temp->clear();
```
