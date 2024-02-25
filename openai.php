
<?php

/*
J'ai installé un xampp, ajouter la key dans l'env.
Puis j'installer composer-setup.exe https://getcomposer.org/download/
puis : composer require openai-php/client
puis : composer require guzzlehttp/guzzle
puis télécharger : https://github.com/openai-php/client
puis installer via composer : composer require openai-php/client
puis mettre des crédits sur https://platform.openai.com/
puis reload le tout, et lancer http://localhost/openai.php 

doc :
https://github.com/openai-php/client/blob/main/src/Resources/Chat.php

https://platform.openai.com/docs/api-reference/chat/create

*/
require_once __DIR__ . '/vendor/autoload.php';

$yourApiKey = getenv('OPENAI_API_KEY');
$client = OpenAI::client($yourApiKey);

$result = $client->chat()->create([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'user', 'content' => 'Traduit moi en anglais ce qui suivra, en respectant la nomenclature'],
		['role' => 'user', 'content' => '# f "Salut les amoureux !"'],
    ],
]);

echo $result->choices[0]->message->content; 
/*
$response = $client->models()->list();

$response->object; // 'list'

foreach ($response->data as $result) {
    $result->id; // 'gpt-3.5-turbo-instruct'
    $result->object; // 'model'
    // ...
}

$response->toArray(); // ['object' => 'list', 'data' => [...]]
*/

// voir son usage :
// https://platform.openai.com/usage