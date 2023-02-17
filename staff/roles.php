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
    let available_permissions = <?= json_encode(Config::$permissions) ?>;
    let permissions_dictionary = <?= json_encode(Config::$permissions_dictionary) ?>;

    function getRoles(id = false) {
        apiclient.get('/api/v2/roles/list').then(({
            data
        }) => {
            if (data.success) {
                $('#staff').html(parseRoles(data.roles));
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
        for (let p of available_permissions) {
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

    function createRole() {
        apiclient.post('/api/v2/roles/add', {
            name: $('#roleName').val()
        }).then(({
            data
        }) => {

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

        let current_role = roles.find(element => {
            return parseInt(element.id) === parseInt(current_role_id);
        });

        apiclient.post(`/api/v2/roles/${current_role.id}/update`, {
            perms: perms,
            name: current_role.name
        }).then(({
            data
        }) => {
            if (data.success) {
                getRoles(current_role.id);
                new Noty({
                    type: 'success',
                    text: data.message,
                    timeout: 1000
                }).show();
            } else {
                new Noty({
                    type: 'error',
                    text: data.message,
                    timeout: 4000
                }).show();
            }
        })
    }

    function shuffle(direction, role_id) {
        apiclient.post(`/api/v2/roles/${role_id}/shuffle`, {
            id: role_id,
            direction
        }).then(({
            data
        }) => {

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
    }

    function deleteRole(forcefully = false) {
        apiclient.post(`/api/v2/roles/${current_role_id}/delete`, {
            forcefully
        }).then(({
            data
        }) => {
            if (data.success) {
                new Noty({
                    type: 'success',
                    text: data.message,
                    timeout: 1000
                }).show();
                getRoles();
                $('#staff_info').html('<h1>Select Role To Modify It</h1>');
            } else {
                if (data.action) {
                    const noty_retry = new Noty({
                        type: 'warning',
                        text: `${data.message}, ${data.action}`,
                        buttons: [
                            Noty.button('Yes', 'yes', () => {
                                deleteRole(true);
                                noty_retry.close();
                            }),
                            Noty.button('No', 'no', () => {
                                noty_retry.close();
                            })
                        ]
                    }).show();
                } else {
                    new Noty({
                        type: 'error',
                        text: data.message,
                        timeout: 10000
                    }).show();
                }
            }
        })
    }
    window.addEventListener("load", () => {
        getRoles();
    })

    tippy('[data-tippy-content]', {
        placement: 'right'
    });
</script>
<style>
    .yes,
    .no {
        width: calc(50% - 12px);
        margin: 0 6px !important;
        border-radius: 8px;
    }

    .yes {
        background-color: rgb(255, 60, 60);
        color: black;
    }

    .no {
        background-color: rgb(60, 120, 200);
        color: black;
    }
</style>