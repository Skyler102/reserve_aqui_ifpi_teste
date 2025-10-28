// Funções de validação de formulários
function validarFormulario(form) {
    'use strict';
    
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    form.classList.add('was-validated');
    return form.checkValidity();
}

// Funções do calendário
function inicializarCalendario(element, eventos, isAdmin = false) {
    const calendar = new FullCalendar.Calendar(element, {
        initialView: 'timeGridWeek',
        locale: 'pt-br',
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: false,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Hoje',
            week: 'Semana',
            day: 'Dia'
        },
        events: eventos,
        eventClassNames: function(arg) {
            return ['bg-' + arg.event.extendedProps.status];
        },
        selectable: !isAdmin,
        select: function(info) {
            if (!isAdmin) {
                $('#modalNovoAgendamento').modal('show');
                $('#data_hora_inicio').val(info.startStr);
                $('#data_hora_fim').val(info.endStr);
            }
        }
    });
    
    calendar.render();
    return calendar;
}

// Funções para lidar com agendamentos
function aprovarAgendamento(id) {
    if (confirm('Deseja realmente aprovar este agendamento?')) {
        fetch(`/api/agendamentos.php?acao=aprovar&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao aprovar o agendamento.');
                }
            });
    }
}

function recusarAgendamento(id) {
    if (confirm('Deseja realmente recusar este agendamento?')) {
        fetch(`/api/agendamentos.php?acao=recusar&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao recusar o agendamento.');
                }
            });
    }
}

// Funções para lidar com recursos
function toggleStatusRecurso(id, status) {
    if (confirm(`Deseja realmente ${status ? 'ativar' : 'desativar'} este recurso?`)) {
        fetch(`/api/recursos.php?acao=toggle_status&id=${id}&status=${status}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao alterar o status do recurso.');
                }
            });
    }
}

// Funções de formatação
function formatarData(data, formato = 'DD/MM/YYYY HH:mm') {
    return moment(data).format(formato);
}

function formatarDuracao(inicio, fim) {
    const duration = moment.duration(moment(fim).diff(moment(inicio)));
    const horas = Math.floor(duration.asHours());
    const minutos = duration.minutes();
    
    return `${horas}h${minutos > 0 ? ` ${minutos}min` : ''}`;
}

// Funções auxiliares
function copiarTexto(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        alert('Texto copiado para a área de transferência!');
    });
}

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa todos os tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializa validação de formulários
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => validarFormulario(form));
    });
});