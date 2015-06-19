<?php $uri = App::getURI() ?>
<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Synapse MVC - Translate Module</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">



        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="<?= $uri->base() ?>app/modules/translate/resources/styles/selectric.css">
        <link rel="stylesheet" href="<?= $uri->base() ?>app/modules/translate/resources/styles/translate.css">

    </head>
    <body>

        <div class="container" id="content">
            <form action="<?= $uri->current() ?>/save" method="post">
                <ul class="nav nav-tabs" role="tablist" id="myTab">
                    <?php foreach($files as $i=>$file): ?>
                    <li class="<?= !$i ? 'active' : '' ?>">
                        <a href="#<?= StringNormalise::toDashSeparated($file->name) ?>" role="tab" data-toggle="tab"><?= $file->filename ?></a>
                    </li>
                    <?php endforeach; ?>

                    <li class="pull-right">
                        <button type="submit" class="btn btn-sm btn-success">Save</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <?php foreach($files as $i=>$file): ?>
                    <div class="tab-pane <?= !$i ? 'active' : '' ?>" id="<?= StringNormalise::toDashSeparated($file->name) ?>">

                        <br />
                        <?php foreach($file->translations as $id=>$translation): ?>
                        <div class="block" id="<?= $id ?>">
                            <div class="original-wrapper">
                                <div class="original-text">
                                    <span class="hasTooltip" data-original-title="Click to edit the original text" data-placement="left"><?= $translation->original ?></span>
                                    <textarea class="form-control input-sm" name="<?= $file->name.'['. $id ?>][original]"><?= $translation->original ?></textarea>
                                </div>
                                <div class="toggle">
                                    <i data-id="<?= $id ?>" class="glyphicon glyphicon-chevron-down hasTooltip" data-original-title="Click to edit the translations"></i>
                                </div>
                            </div>

                            <div class="translations-wrapper">
                                <ul class="list-group">
                                    <?php foreach($translation->translations as $lang=>$text): ?>
                                    <li class="list-group-item">
                                        <div class="language">
                                            <select class="select" data-id="<?= $file->name.'['.$id.']' ?>">
                                                <option value="">-</option>
                                                <?php foreach($languages as $i=>$language): ?>
                                                <option value="<?= $language ?>" <?= ($lang == $language) ? 'selected' : '' ?>><?= $language ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="translation">
                                            <textarea class="form-control input-sm" rows="1" name="<?= $file->name.'['.$id ?>][translations][<?= $lang ?>]"><?= $text ?></textarea>
                                        </div>
                                        <div class="delete">
                                            <button type="button" class="close hasTooltip" data-original-title="Delete translation" data-placement="right">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="clearfix"></div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>

                                <a href="<?= $uri->current() ?>/delete/<?= $file->name ?>/<?= $id ?>" class="btn btn-danger btn-xs">Delete</a>
                                <button class="btn btn-default btn-xs pull-right hasTooltip add-translation" data-original-title="Add new language" data-id="<?= $id ?>" data-file="<?= $file->name ?>">
                                    <i class="glyphicon glyphicon-plus"></i>
                                </button>
                                <div class="divider"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <div class="new-translation">
            <button class="btn btn-sm btn-default" data-toggle="modal" data-target="#newModal">Add a new translation</button>
        </div>


        <div class="modal fade" id="newModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                            <span class="sr-only">Close</span>
                        </button>
                        <h4 class="modal-title">New translation</h4>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="<?= $uri->current() ?>/new">
                            <div class="form-group">
                                <label>File</label>
                                <select class="form-control" name="file" onchange="$(this).val() == 1 ? $('#newFileName').show() : $('#newFileName').hide().children('input').val('')" required>
                                    <option>-- Select json file --</option>
                                    <?php foreach ($files as $i=>$file): ?>
                                    <option value="<?= $file->name ?>"><?= $file->name ?></option>
                                    <?php endforeach; ?>
                                    <option disabled></option>
                                    <option value="1">-- Create a new file --</option>
                                </select>
                            </div>
                            <div class="form-group" id="newFileName" style="display: none;">
                                <label>File name</label>
                                <input type="text" class="form-control" name="filename" />
                            </div>
                            <div class="form-group">
                                <label>Original text</label>
                                <textarea class="form-control" rows="10" name="original"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal" onclick="$('#newModal textarea, #newModal input').val('');$('#newModal select').val('');">Close</button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="$('#newModal form').submit()">Add</button>
                    </div>
                </div>
            </div>
        </div>


        <script>var modURL = '<?= $uri->base() ?>app/modules/translate/resources/images/';</script>
        <script>var languages = <?= json_encode($languages) ?></script>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
        <script src="<?= $uri->base() ?>app/modules/translate/resources/scripts/jquery.selectric.min.js"></script>
        <script src="<?= $uri->base() ?>app/modules/translate/resources/scripts/translate.js"></script>
    </body>
</html>