<aside id="notifications">
</aside>
<div class="col-lg-12">
    <div class="row">
        <div class="col-lg-8">
            {$output}
            <div class="panel" data-report>
                <div id="upload_file_progress-bar" class="progress hidden">
                    <div class="progress-bar" role="progressbar"></div>
                </div>
            </div>
            <div class="panel" data-report>
                <h3><i class="icon-list-alt"></i> Report</h3>
                <div class="import-report" class="row">
                    <div class="col-sm-6 col-lg-4">
                        <div>
                            <div class="import-report__content">
                                <i class="icon-flag"></i>
                                <span class="title">Всего обработано</span>
                                <span class="value" data-report="total">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div>
                            <div class="import-report__content">
                                <i class="icon-chain-broken"></i>
                                <span class="title">Не найдено</span>
                                <span class="value" data-report="fail">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div>
                            <div class="import-report__content">
                                <i class="icon-check"></i>
                                <span class="title">Обновлено</span>
                                <span class="value" data-report="pass">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <a class="btn btn-default btn-lg btn-block hidden" data-action="downloadLog" href="#">
                    <i class="icon-download"></i>
                    Скачать лог файл
                </a>
                <button type="button" class="btn btn-primary btn-lg btn-block hidden" data-action="close">Закрыть</button>
            </div>
            <div class="panel" data-report>
                <h3><i class="icon-list-alt"></i> Не найденные позиции (код товара)</h3>
                <div class="row" data-report="skuList">
                    <div class="list-empty-msg">
                        <i class="icon-warning-sign list-empty-icon"></i>
                        Записей нет
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel" id="b-export">
                <h3>
                    <i class="icon-list-alt"></i>
                    Эксорт
                </h3>
                <a href="#" data-action="downloadPrice" type="button" class="btn btn-primary" disabled="disabled">Скачать</a>
                <button data-action="exportPrice" type="button" class="btn btn-default">Экспорт прайса</button>
            </div>
            <div class="panel">
                <h3>
                    <i class="icon-list-alt"></i>
                    Доступные поля
                </h3>
                <div id="availableFields" class="alert alert-info">
                    <div>Код товара</div>
                    <div>Название</div>
                    <div>Цена&nbsp;<a href="#" class="help-tooltip" data-toggle="tooltip" title=""
                                      data-original-title="Только целые числа, без дробной части"><i
                                    class="icon-info-sign"></i></a></div>
                    <div>Производитель</div>
                    <div>Ссылка на товар</div>
                </div>
                <div><b>Важно:</b> Поля `Код товара` и `Цена` - обязательны для файла импорта!</div>
            </div>
        </div>
    </div>
</div>