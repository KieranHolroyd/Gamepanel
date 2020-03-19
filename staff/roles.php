<?php include "../head.php";
include $_SERVER['DOCUMENT_ROOT'] . '/classes/Interviews.php';
Guard::init()->SLTRequired();
?>
<div class="grid new">
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new">Role Management</h1>
        <div id="staff" class="selectionPanel">

        </div>
    </div>
    <button onclick="toggleNewRole()" data-tippy-content="New Role" class="newPointBtn">+</button>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer">
            <div class="infoPanel" id="staff_info">
                <div class="pre_title">Select Role For Details</div>
            </div>
        </div>
    </div>
</div>
<div class="drawer" id="newRole">
    <div class="field">
        <div class="fieldTitle">Role Name</div>
        <input type="text" id="roleName" placeholder="Role Name">
    </div>
    <button class="createPointBtn" onclick="createRole()">Create Role</button>
</div>
<script>
    let open = false;
    let roles = [];
    let current_role_id;
    let available_permissions = <?= json_encode(Config::$permissions)?>;
    let permissions_dictionary = <?= json_encode(Config::$permissions_dictionary)?>;

    function getRoles(id = false) {
        $.get('/api/roles', data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                $('#staff').html(parseRoles(data.response));
                if (id) {
                    openRole(id);
                }
            } else {
                $('#staff').html(`An Error Occoured: ${data.message}`);
            }
        });
    }

    function parseRoles(r) {
        let parsed = '';

        roles = [];

        for (let role of r) {
            roles.push(role);
            parsed += `<div class="selectionRow">
                       <div class="Control">
                           <span onclick="shuffle('UP', ${role.id})"><i class="fas fa-sort-up"></i></span>
                           <span onclick="shuffle('DOWN', ${role.id})"><i class="fas fa-sort-down"></i></span>
                       </div>
                       <div class="Tab"
                            onclick="openRole(${role.id})">${role.name}</div></div>`;
        }

        return parsed;
    }

    function openRole(id) {
        current_role_id = id;

        let roleInfo = roles.find(element => {
            return parseInt(element.id) === parseInt(id);
        });

        $('#staff_info').html(`<h2>${roleInfo.name} <i ondblclick="deleteRole()" data-tippy-content="Double Click To Delete Role" style="float: right;cursor: pointer;" class="fas fa-trash-alt"></i></h2> <p>Current Permissions: ${roleInfo.permissions.join(', ')}</p> <div id="opts" class="options">${generateEditor(roleInfo.permissions)}</div><div class="btnGroup"><button onclick="updateCurrentRole()">Update Role Permissions</button></div>`);

        tippy('[data-tippy-content]', {
            placement: 'bottom'
        });
    }

    function generateEditor(perms) {
        let editor = ``;
        for(let p of available_permissions) {
            let matched = false;
            for (let pp of perms) {
                if (p === pp) matched = true;
                if (pp === "*") matched = true;
            }

            let tippy = "";
            if (permissions_dictionary[p] !== undefined) tippy = `data-tippy-content="${permissions_dictionary[p]}"`;

            editor += `<div ${tippy} class="option"><label for="perm-${p}">${p}</label> <input onclick="toggleOption(event)" class="checkbox" type="checkbox" id="perm-${p}" value="${p}" ${(matched === true) ? " checked" : ""}></div>`;
        }
        return editor;
    }

    function toggleOption(opt) {
        if (opt.target.id === "perm-*") {
            if (opt.target.checked) {
                $('input[id*="perm-"]').prop('checked', true);
            } else {
                $('input[id*="perm-"]').prop('checked', false);
            }
        }
    }

    getRoles();

    function createRole() {
        $.post('/api/newRole', {
            name: $('#roleName').val()
        }, data => {
            data = JSON.parse(data);

            console.log(data);
            if (data.code === 200) {
                getRoles();
                $('#roleName').val('');
                $('#newRole').removeClass('open');
                $('.newPointBtn').removeClass('open');
                open = false;
            } else {
                new Noty({
                    type: 'error',
                    text: data.message,
                    timeout: 4000
                }).show();
            }
        })
    }

    function toggleNewRole() {
        if (!open) {
            $('#newRole').addClass('open');
            $('.newPointBtn').addClass('open');
        } else {
            $('#newRole').removeClass('open');
            $('.newPointBtn').removeClass('open');
        }
        open = !open;
    }

    function updateCurrentRole() {
        let perms = [];

        $('input[id*="perm-"]').each((k, v) => {
            if (v.checked) {
                perms.push(v.value);
            }
        });

        $.post('/api/updateRolePermissions', {
            perms: perms,
            roleID: current_role_id
        }, data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                getRoles(current_role_id);
            } else {
                new Noty({
                    type: 'error',
                    text: data.message,
                    timeout: 4000
                }).show();
            }
        })
    }

    function shuffle(direction, elID) {
        switch(direction) {
            case 'UP':
                $.post('/api/shuffleUpRole', {
                    id: elID
                }, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        getRoles();
                    } else {
                        new Noty({
                            type: 'error',
                            text: data.message,
                            timeout: 4000
                        }).show();
                    }
                });
                break;
            case 'DOWN':
                $.post('/api/shuffleDownRole', {
                    id: elID
                }, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        getRoles();
                    } else {
                        new Noty({
                            type: 'error',
                            text: data.message,
                            timeout: 4000
                        }).show();
                    }
                });
                break;
        }
    }

    function deleteRole() {
        $.post('/api/deleteRole', {
            id: current_role_id
        }, data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                getRoles();
                $('#staff_info').html('<h1>Select Role To Modify It</h1>');
            } else {
                new Noty({
                    type: 'error',
                    text: data.message,
                    timeout: 4000
                }).show();
            }
        })
    }

    tippy('[data-tippy-content]', {
        placement: 'right'
    });
</script>