In PHP / XAMPP / OPENAI Playground - Universal Translator.

In 3 steps :
- Step 1 : the script extract data from RPY. (stock in bdd).
- Step 2 : For each line to translate, we send a prompt + the text to translate, we get the answer, stock in cache
- Step 3 : Rewrite rpy with translation.

The script handles interrupts in the event of api problems. It will pick up where it left off (with an F5).
The script uses a CACHE system to avoid translating the same sentence twice, from one file to another.

************************************************************

				CREATE BY SecretGame18
************************************************************

NO COMMERCIAL USE.
You can use this script, modify etc if you "show the creator".
You can't use it for win money.

Try my games : 
https://www.lucie-adult-game.com/

https://www.corrupted-paradise.com/

Patreon : https://www.patreon.com/lucie_adult_game

If you use it or like it, please support me on Patreon

************************************************************

		How to install ?
************************************************************

Create an account on OpenAI Playground. Obtain a Key.

Intall webserver with php >8.1

Exemple xampp

- Open httpd.conf, ajouter SetEnv OPENAI_API_KEY sk-**********************************

- Charge SQL file in database. 

- Install composer for add plugin : https://getcomposer.org/download/

- Lauch and install composer-setup.exe

After, lauch in line command (in c:\xampp\httpdocs\)

- composer require openai-php/client

- composer require guzzlehttp/guzzle

- composer require openai-php/client


Download zip : https://github.com/openai-php/client

Add credit on openai (with credit card on https://platform.openai.com/)

You can test with : http://localhost/openai.php 

Documentation about api php and openai :

https://github.com/openai-php/client/blob/main/src/Resources/Chat.php

https://platform.openai.com/docs/api-reference/chat/create


************************************************************

		How to USE ?
************************************************************
- Put rpy (translate file) in C:\xampp\htdocs\renpy_auto_translate\file_translate
- Lauch in firefox localhost/renpy_auto_translate/1-renpy_translation_read_rpy.php
- Follow the link step2 (wait a few moment, external api call on openai)
- Follow the link to step3 (rewrite rpy file in  C:\xampp\htdocs\renpy_auto_translate\file_to_translate)

- Try always with test file before !

