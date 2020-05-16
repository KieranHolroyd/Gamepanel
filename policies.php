<?php include "head.php";
Guard::init()->StaffRequired();
?>
<!--<script src="https://cdn.ckeditor.com/4.8.0/standard/ckeditor.js"></script>-->
    <script src="https://cdn.ckeditor.com/ckeditor5/11.2.0/classic/ckeditor.js"></script>
    <div class="grid new">
        <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
            <h1 class="info-title new"><?= Config::$name; ?> Operating Policies</h1>
            <div id="guides" class="selectionPanel">

            </div>
            <div id="cngcont" style="display: none;">
                <div style="height: 50px;"></div>
            </div>
        </div>
        <div class="grid__col grid__col--4-of-6">
            <div class="infoPanelContainer">
                <div class="infoPanel" id="guide_info">
                    <div class="pre_title">Select Policy For Details</div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal" id="createGuide">
        <button id="close">×</button>
        <div class="content" style="max-width: 900px;padding:0;">
            <div class="field">
                <div class='fieldTitle'>Policy Title</div>
                <input id="Gtitle" type="text" class='fieldInput' placeholder="Policy Title"></div>
            <div class="field">
                <div class='fieldTitle'>Policy Body</div>
                <textarea id="guide_body"></textarea></div>
            <button style='width:100%;margin: 0;' id="createButton">Create Policy</button>
        </div>
    </div>
    <div class="modal" id="editGuide">
        <button id="close">×</button>
        <div class="content" style="max-width: 900px;padding:0;">
            <div class="field">
                <div class='fieldTitle'>Policy Title</div>
                <input id="Gtitle_edit" type="text" class='fieldInput' placeholder="Policy Title"></div>
            <div class="field">
                <div class='fieldTitle'>Policy Body</div>
                <textarea id="guide_body_edit"></textarea></div>
            <button style='width:100%;margin: 0;' id="editButton">Update Policy</button>
        </div>
    </div>
    <?php if($user->isSLT()): ?>
    <button id="modalLaunch" launch="createGuide" class="newPointBtn">+</button>
    <button id="modalLaunch" launch="editGuide" onclick="edit_open()" class="newEditBtn"><i style="font-size: 14px;" class="fas fa-pen"></i></button>
    <?php endif; ?>
    <style>
        .ck-editor__editable p {
            color: #000;
        }
        .ck-editor__editable {
            min-height: 400px;
        }
    </style>
    <script>
        let list, guide, item, currently_editing, editor, edit_editor;

        ClassicEditor.create(document.querySelector('#guide_body')).then(e => {editor = e;}).catch(error => {
            console.error(error);
        });
        ClassicEditor.create(document.querySelector('#guide_body_edit')).then(e => {edit_editor = e;}).catch(error => {
            console.error(error);
        });

        function checkIfSLT() {
            if (userArray.info.slt == 1 || userArray.info.dev == 1) {
                $('#cngcont').slideDown(100);
            }
        }

        function getGuides() {
            list = "";
            $.get('api/getGuides', function (data) {
                let guides = JSON.parse(data);
                for (let i = 1; i < Object.keys(guides).length + 1; i++) {
                    list += `<div class="selectionTab" onclick="getFullGuide(${guides[i].id})"><span style="float: right;font-size: 12px;">Effective: ${guides[i].effective}</span><span style="font-size: 25px;">${guides[i].title}<br></span></div>`;
                }
                $('#guides').html(list);
                checkIfSLT();
            });
        }

        function getFullGuide(id) {
            $('#guide_info').html("<img src='../../Before/Purple-Iron-Bulldog/img/loadw.svg'>");
            item = "";
            $.post('api/getFullGuide', {'id': id}, function (data) {
                currently_editing = id;
                guide = JSON.parse(data);
                item += `<h1>${guide.title}</h1><p>Last Updated: ${guide.time} | Effective: ${guide.effective} | Author: ${guide.author}</p><div>${guide.body}</div>`;
                $('#guide_info').html(item);
                $('.newEditBtn').addClass('show');
            });
        }

        getGuides();

        function newGuide() {
            if (userArray.info.slt == 1) {
                $.post('api/addGuide', {
                    'title': $('#Gtitle').val(),
                    'body': editor.getData()
                }, function (data) {
                    $('#Gtitle').val('');
                    editor.setData('');
                    console.log(data);
                    getGuides();
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        theme: 'metroui',
                        timeout: 3000,
                        text: data,
                    }).show();
                });
            } else {
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    theme: 'metroui',
                    timeout: 3000,
                    text: "You Must Be SLT To Submit A Guide",
                }).show();
            }
        }

        function edit_open() {
            let id = currently_editing;
            $.post('api/getFullGuide', {'id': id}, function (data) {
                guide = JSON.parse(data);
                $('#Gtitle_edit').val(guide.title);
                edit_editor.setData(guide.body);
            });
        }

        function editGuide() {
            $.post('api/editGuide', {
                'id': currently_editing,
                'title': $('#Gtitle_edit').val(),
                'body': edit_editor.getData()
            }, function (data) {
                getFullGuide(currently_editing);
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    theme: 'metroui',
                    timeout: 3000,
                    text: data,
                }).show();
            });
        }

        $('#createButton').click(function () {
            newGuide();
        });
        $('#editButton').click(function () {
            editGuide();
        });
    </script>