# Dangerous Functions Checker


# Алгоритм поиска вирусов

* Выкачать сайт локально

	`lftp -f lftp/mirror.txt`

* Проверить сайт этим скриптом. Результаты проверки будут в каталоге detected. Эти файлы проверяем вручную, названия подозрительных вручную заносим в файл suspected.txt (один файл на строку). Отдельно в файл duplicates.txt записываем названия групп одинаковых файлов, часто это шеллы. Найденные большие блоки в кодировке base64 скрипт вырежет из файлов, если файлы при этом опустеют, то эти файлы скрипт удалит и их названия запишет в файл files_del.txt. Названия почищенных файлов будут в файле files_repl.txt. Также будут удалены однострочные вирусы и вставки вирусов в начало файлов.

	`php -f 1_scan.php www`

* Делаем резервную копию файлов, отмеченных как подозрительные, в каталог virii

	`php -f 2_backup_virii.php suspected.txt`

* Вручную просматриваем каждый файл из подозрительных, вирусы удаляем, файлы сохраняем

	`./3_view.sh suspected.txt`

* Проводим анализ сделанных нами изменений по сравнению с каталогом virii. Если файл изменён, оставляем его копию в каталоге virii и записываем его название в файл files_repl.txt. Если размер файла - 0 или 1 (т. е. его содержимое удалено), то удаляем этот файл, его копию оставляем в каталоге virii и записываем его название в файл files_del.txt. Если файл идентичен своей копии, лежащей в virii, т. е. файл не изменился, значит, файл не заражён, удаляем его копию из каталога virii.

	`php -f 4_diff_virii.php suspected.txt`

* Файлы для удаления с хостинга и замены на хостинге можно добавить в скрипт lftp для автоматизированной обработки, примеры лежат в каталоге lftp.

* Также есть скрипт для вытаскивания из отчёта айболита  (https://revisium.com/ai/) файлов, которые он в чём-то заподозрил. Лучше визуально проверить, все ли файлы он нашёл и не нашёл ли лишнего (скрипт пока не идеален). Найденные файлы добавляем в suspected.txt и проверяем их по схеме, описанной выше.

	`php -f parse_aibolit_report.php aibolit_report.html`

