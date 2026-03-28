function cargarGrupos() {
    const idGrado = document.getElementById('sel_grado_asig').value;
    const selectGrupo = document.getElementById('sel_grupo_asig');

    selectGrupo.innerHTML = '<option value="">— Cargando... —</option>';

    if (!idGrado) {
        selectGrupo.innerHTML = '<option value="">— Primero seleccione grado —</option>';
        return;
    }

    fetch(`../pages/obtener_grupos.php?idGrado=${idGrado}`)
        .then(r => r.json())
        .then(grupos => {
            if (grupos.length === 0) {
                selectGrupo.innerHTML = '<option value="">— No hay grupos —</option>';
            } else {
                selectGrupo.innerHTML = '<option value="">— Seleccionar —</option>';
                grupos.forEach(g => {
                    selectGrupo.innerHTML += `<option value="${g.idGrupo}">${g.nombre}</option>`;
                });
            }
        });
}

function cargarGruposMat() {
    const idGrado = document.getElementById('sel_grado_mat').value;
    const selectGrupo = document.getElementById('sel_grupo_mat');

    selectGrupo.innerHTML = '<option value="">— Cargando... —</option>';

    if (!idGrado) {
        selectGrupo.innerHTML = '<option value="">— Seleccionar Sede/Grado —</option>';
        return;
    }

    fetch(`../pages/obtener_grupos.php?idGrado=${idGrado}`)
        .then(r => r.json())
        .then(grupos => {
            if (grupos.length === 0) {
                selectGrupo.innerHTML = '<option value="">— No hay grupos —</option>';
            } else {
                selectGrupo.innerHTML = '<option value="">— Seleccionar —</option>';
                grupos.forEach(g => {
                    selectGrupo.innerHTML += `<option value="${g.idGrupo}">${g.nombre}</option>`;
                });
            }
        });
}

function cargarGruposProm() {
    const idGrado = document.getElementById('sel_grado_prom').value;
    const selectGrupo = document.getElementById('sel_grupo_prom');

    selectGrupo.innerHTML = '<option value="">— Cargando... —</option>';

    if (!idGrado) {
        selectGrupo.innerHTML = '<option value="">— Seleccionar Grado primero —</option>';
        return;
    }

    fetch(`../pages/obtener_grupos.php?idGrado=${idGrado}`)
        .then(r => r.json())
        .then(grupos => {
            if (grupos.length === 0) {
                selectGrupo.innerHTML = '<option value="">— No hay grupos —</option>';
            } else {
                selectGrupo.innerHTML = '<option value="">— Seleccionar —</option>';
                grupos.forEach(g => {
                    selectGrupo.innerHTML += `<option value="${g.idGrupo}">${g.nombre}</option>`;
                });
            }
        });
}

function cargarGruposCalif() {
    const idGrado = document.querySelector('select[name="idGrado"]').value;
    const selectGrupo = document.getElementById('sel_grupo_cal');

    selectGrupo.innerHTML = '<option value="">— Cargando... —</option>';

    if (!idGrado) {
        selectGrupo.innerHTML = '<option value="">— Seleccionar Grupo —</option>';
        return;
    }

    fetch(`../pages/obtener_grupos.php?idGrado=${idGrado}`)
        .then(r => r.json())
        .then(grupos => {
            if (grupos.length === 0) {
                selectGrupo.innerHTML = '<option value="">— No hay grupos —</option>';
            } else {
                selectGrupo.innerHTML = '<option value="">— Seleccionar Grupo —</option>';
                grupos.forEach(g => {
                    selectGrupo.innerHTML += `<option value="${g.idGrupo}">${g.nombre}</option>`;
                });
            }
        });
}