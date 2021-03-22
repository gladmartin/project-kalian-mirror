$(function () {
    $('select').selectric();
});

const PROJECT_KALIAN_ENDPOINT = 'project.php';
let projects = [];
let projectsData = [];

async function fetchProjectList() {
    $('.project-list-loading').show();
    let res = await fetch(PROJECT_KALIAN_ENDPOINT);
    let result = await res.json();
    if (result.success == false) {
        $('.project-list-loading').hide();
        $('.project-list').html(`<div class="col-12 mt-5"><div class="alert alert-danger">Failed to load data projects, <br> Response message <b> ${result.message}</b></div></div>`)
        return;
    }
    projects.push(result.data[0]);
    projectsData = result.data;
    let i = 0;
    for (const project of projectsData) {
        $('#periode').append(`<option ${i == 0 ? 'selected' : ''}>${project.periode}</option>`)
        $('#periode').selectric('refresh');
        i++;
    }
    buildCardProjects();
}

function buildCardProjects() {
    if (projects.length == 0) return;
    let content = '';
    for (const project of projects) {
        content += `<div class="mt-5"><h4 class="text-end text-muted">${project.periode}</h4></div>`;
        for (const item of project.items) {
            content += cardHtml(item);
        }
    }
    $('.project-list').html(content)
    $('.project-list-loading').hide();
}

function cardHtml(project) {
    return `
    <div class="col-lg-4 mb-4 col-project" data-id="${project.demo_link}">
    <div class="card project shadow-sm">
        <div class="card-header bg-white">
            <div class="view-demo">
                <a href="${project.demo_link}" target="_blank" class="text noselect">Visit demo</a>
            </div>
            <div class="logo shadow-sm noselect" style="background-color: ${project.metas.color_profile}">${project.metas.initial}</div>
        </div>
        <div class="card-body mt-5 px-4 pb-4">
            <h3>${project.author || 'No Name'}</h3>
            <div class="description limit">${project.description || '<i>Tidak ada deskripsi</i>'}</div>
        </div>
        <div class="card-footer bg-white border-0 p-3">
            <button class="btn btn-secondary btn-sm detail-project" data-id="${project.demo_link}" data-des="${encodeURIComponent(project.description_full)}">Detail Project</button>
            <a class="btn btn-dark btn-sm ${!project.github_link ? 'no-github' : ''}" target="blank" title="${project.github_link}" href="${project.github_link || ''}">GitHub Repo</a>
        </div>
    </div>
</div>`}

$('#periode').on('change', async function (e) {
    let val = $(this).val();
    $('.project-list-loading').show();
    $('.project-list').html('');
    if (val == 'all') {
        projects = projectsData;
    } else {
        projects = [];
        projectsData.forEach(element => {
            if (element.periode == val) {
                projects.push(element);
                return;
            }
        });
    }
    await new Promise(r => setTimeout(r, 500));
    buildCardProjects();
});

var myModal = new bootstrap.Modal(document.getElementById('myModal'))
$('body').on('click', '.detail-project', function (e) {
    e.preventDefault();
    let projectId = $(this).data('id');
    let des = decodeURIComponent($(this).data('des'));
    $('.modal-body').html(des);
    myModal.show()
})



fetchProjectList();


