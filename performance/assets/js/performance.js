(function ($) {
  function showToast(message) {
    var toast = $('#performanceToast');
    toast.find('.performance-toast-message').text(message || 'Saved successfully.');
    toast.stop(true, true).fadeIn(150);
    clearTimeout(window.performanceToastTimer);
    window.performanceToastTimer = setTimeout(function () {
      toast.fadeOut(220);
    }, 2400);
  }

  function initLoading() {
    setTimeout(function () {
      $('.performance-loading').fadeOut(180, function () {
        $(this).remove();
        $('.performance-module-main').removeClass('d-none');
      });
    }, 350);
  }

  function initModals() {
    $(document).on('click', '.js-performance-modal', function (event) {
      event.preventDefault();
      var trigger = $(this);
      var modalId = trigger.data('modal');
      if (!modalId) {
        return;
      }
      var element = document.getElementById(modalId);
      if (!element) {
        return;
      }
      var modal = $(element);
      var form = modal.find('form').first();
      if (form.length && form[0]) {
        form[0].reset();
      }
      modal.find('.js-performance-record-id').val('');
      modal.find('.js-performance-modal-title').text(modal.data('create-title') || 'Quick Action');
      modal.find('.js-performance-submit-button').text('Save');

      var rawValues = trigger.attr('data-form-values');
      if (rawValues) {
        try {
          var values = JSON.parse(rawValues);
          Object.keys(values).forEach(function (key) {
            var field = form.find('[name="' + key + '"]');
            if (!field.length) {
              return;
            }
            field.val(values[key]);
          });
          modal.find('.js-performance-modal-title').text(trigger.data('edit-title') || ('Edit ' + (modal.data('create-title') || 'Record')));
          modal.find('.js-performance-submit-button').text('Update');
        } catch (error) {
        }
      }
      new bootstrap.Modal(element).show();
    });
  }

  function initAjaxForms() {
    $(document).on('submit', '.js-performance-form', function (event) {
      event.preventDefault();
      var form = $(this);
      var button = form.find('button[type="submit"]');
      var formData = new FormData(this);
      formData.append('action', form.data('action'));
      button.prop('disabled', true);

      $.ajax({
        url: window.performanceModule.ajaxUrl,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json'
      }).done(function (response) {
        showToast(response.message || 'Saved successfully.');
        if (response.status === 'success') {
          form.trigger('reset');
          var modal = form.closest('.modal');
          if (modal.length) {
            var instance = bootstrap.Modal.getInstance(modal[0]);
            if (instance) {
              instance.hide();
            }
          }
          if (response.reload) {
            setTimeout(function () {
              window.location.reload();
            }, 500);
          }
        }
      }).fail(function () {
        showToast('Unable to save right now.');
      }).always(function () {
        button.prop('disabled', false);
      });
    });
  }

  function renderChart(id, config) {
    var element = document.getElementById(id);
    if (!element || typeof Chart === 'undefined') {
      return;
    }
    new Chart(element, config);
  }

  function initCharts() {
    var data = window.performanceModule.charts || null;
    if (!data || typeof Chart === 'undefined') {
      return;
    }

    renderChart('performanceTrendChart', {
      type: 'line',
      data: {
        labels: data.trendLabels,
        datasets: [{
          data: data.trendScores,
          borderColor: '#123b76',
          backgroundColor: 'rgba(18,59,118,0.1)',
          fill: true,
          tension: 0.35,
          borderWidth: 3,
          pointRadius: 4,
          pointBackgroundColor: '#123b76'
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: false, grid: { color: 'rgba(148,163,184,0.18)' } }, x: { grid: { display: false } } }
      }
    });

    renderChart('goalStatusChart', {
      type: 'doughnut',
      data: {
        labels: data.goalStatusLabels,
        datasets: [{
          data: data.goalStatusValues,
          backgroundColor: ['#123b76', '#22c55e', '#facc15', '#ef4444'],
          borderWidth: 0
        }]
      },
      options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom' } } }
    });

    renderChart('departmentPerformanceChart', {
      type: 'bar',
      data: {
        labels: data.departmentLabels,
        datasets: [{
          data: data.departmentScores,
          borderRadius: 12,
          backgroundColor: ['#123b76', '#16324f', '#22c55e', '#0ea5e9', '#f59e0b', '#64748b']
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100 }, x: { grid: { display: false } } }
      }
    });

    renderChart('reviewRadarChart', {
      type: 'radar',
      data: {
        labels: data.radarLabels,
        datasets: [{
          data: data.radarValues,
          backgroundColor: 'rgba(34,197,94,0.15)',
          borderColor: '#22c55e',
          pointBackgroundColor: '#22c55e'
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { r: { min: 0, max: 100 } }
      }
    });
  }

  $(function () {
    initLoading();
    initModals();
    initAjaxForms();
    initCharts();
  });
})(window.jQuery);
