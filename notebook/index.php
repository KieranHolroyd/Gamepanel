<?php include "../head.php";
Guard::init()->LoginRequired();
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.42.2/codemirror.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.42.2/mode/markdown/markdown.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.42.2/addon/selection/active-line.js"></script>
<link rel="stylesheet" href="https://www.nitrexdesigncode.com/plugins/codemirror/lib/codemirror.css">
<div class="grid new">
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new">Staff Notebook [BETA]
            <button style="margin: 0;background-color: #4286f4;border-radius: 4px;float: right;" onclick="launchModal(`createPage`);"><i class="fas fa-file"></i></button></h1>
        <div id="pages" class="selectionPanel">

        </div>
    </div>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer">
            <div id="staff_info" class="infoPanel">
                <div class="pre_title" id="help-openpage">Select Page For Details</div>
                <label for="editor" style="display: none;">Editor</label>
                <div id="editorContainer">
                    <textarea id="editor"># Hello, World</textarea>
                    <button onclick="save()" id="saveEditorButton" class="saveButton"><i class="fas fa-save"></i></button>
                    <button onclick="getPageFormatted(true)" id="saveEditorButton" class="openButton"><i class="fas fa-external-link-square-alt"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="viewPage">
    <button id="close">×</button>
    <div class="content" style="max-width: 600px;border-radius: 5px;">
        <h2 id="page_title">Title</h2>
        <div id="page_formatted"></div>
    </div>
</div>
<div class="modal" id="createPage">
    <button id="close">×</button>
    <div class="content" style="max-width: 600px;border-radius: 5px;">
        <h2>Create A New Page</h2>
        <input type="text" id="p-title" class="fieldInput" placeholder="Page Title">
        <div class="btnGroup">
            <button onclick="createPage()">Create</button>
        </div>
    </div>
</div>
<style>
    #editorContainer {
        overflow: hidden;
        border-radius: 6px;
        box-shadow: 0 4px 10px 0 rgba(0,0,0,0.2);
        opacity: 0;
        transition: 200ms;
        transform: scale(0.8);
        padding: 15px;
        background-color: #272828;
    }
    #editorContainer.show {
        opacity: 1;
        transform: scale(1);
    }
    #page_title {
        padding: 10px 15px;
        background-color: #2c2f50;
        border-radius: 4px;
        text-transform: uppercase;
        color: #ccc;
        font-size: 20px;
        letter-spacing: 2px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.14);
    }
    #page_formatted {
        padding: 15px;
        background-color: #2c2f50;
        border-radius: 4px;
        box-shadow: 0 4px 12px 0 rgba(0,0,0,0.14);
    }
    .saveButton {
        margin: 0;
        border-radius: 24px;
        height: 48px;
        width: 48px;
        box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.2);
        position: absolute;
        right: 10px;
        bottom: 10px;
        font-size: 20px;
    }
    .openButton {
        margin: 0;
        border-radius: 24px;
        height: 48px;
        width: 48px;
        box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.2);
        position: absolute;
        right: 70px;
        bottom: 10px;
        font-size: 20px;
    }
</style>
<script>
    let editor;
    let currentlyOpen;

    function createPage() {
        $.post('/api/createPage', {
            title: $('#p-title').val()
        }, data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                getPages();
                $('#p-title').val('');
                new Noty({
                    text: data.message,
                    type: 'success',
                    timeout: 1000
                }).show();
            } else {
                new Noty({
                    text: data.message,
                    type: 'error',
                    timeout: 3000
                }).show();
            }
        })
    }

    function getPages() {
        $.get('/api/pages', data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                let pages = ``;
                for (let i = 0; i < data.response.length; i++) {
                    const page = data.response[i];
                    pages += `<div class="selectionTab" onclick="getPage(${page.id})"><h2>${page.title}</h2></div>`;
                }
                $('#pages').html(pages);
                if (data.response.length === 0) $('#pages').html('<h3>No Pages Found</h3>');
            } else {
                new Noty({
                    text: data.message,
                    type: 'error',
                    timeout: 4000
                }).show();
            }
        });
    }

    function getPage(id) {
        $.get('/api/page?id=' + id, data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                currentlyOpen = data.response.id;

                editor.setValue(data.response.content);

                $('#editorContainer').addClass('show');
                $('#help-openpage').hide(200);
            } else {
                new Noty({
                    text: data.message,
                    type: 'error',
                    timeout: 4000
                }).show();
            }
        });
    }

    function getPageFormatted(current = false, id = 0) {
        if (current) id = currentlyOpen;
        $.get('/api/page_formatted?id=' + id, data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                launchModal('viewPage');

                $('#page_formatted').html(data.response.page);
                $('#page_title').html(data.response.title);
            } else {
                new Noty({
                    text: data.message,
                    type: 'error',
                    timeout: 4000
                }).show();
            }
        });
    }

    function save() {
        $.post('/api/savePage', {
            content: editor.getValue(),
            id: currentlyOpen
        }, data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                new Noty({
                    text: data.message,
                    type: 'success',
                    timeout: 1000
                }).show();
            } else {
                new Noty({
                    text: data.message,
                    type: 'error',
                    timeout: 3000
                }).show();
            }
        })
    }

    $(document).ready(() => {
        editor = CodeMirror.fromTextArea(document.querySelector('#editor'), {
            mode: "markdown",
            lineWrapping: true,
        });

        getPages();
    });
</script>