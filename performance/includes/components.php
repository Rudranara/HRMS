<?php

require_once __DIR__ . '/helpers.php';

function performance_render_status($status)
{
    echo '<span class="performance-status performance-status-' . performance_escape(performance_status_class($status)) . '">' . performance_escape($status) . '</span>';
}

function performance_render_progress($value, $tone)
{
    $value = max(0, min(100, (float) $value));
    echo '<div class="performance-progress-track"><div class="performance-progress-fill tone-' . performance_escape($tone) . '" style="width:' . $value . '%"></div></div>';
}

function performance_render_metric_card($metric)
{
    ?>
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card performance-card performance-hover h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
              <p class="performance-label mb-2"><?= performance_escape($metric['title']) ?></p>
              <h3 class="performance-value mb-0"><?= performance_escape($metric['value']) ?></h3>
            </div>
            <span class="performance-icon tone-<?= performance_escape($metric['tone']) ?>"><i class="<?= performance_escape($metric['icon']) ?>"></i></span>
          </div>
          <p class="text-sm text-secondary mb-0"><?= performance_escape($metric['meta']) ?></p>
        </div>
      </div>
    </div>
    <?php
}

function performance_render_page_head($title, $subtitle, $actions)
{
    ?>
    <div class="performance-page-head mb-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <p class="performance-eyebrow mb-2">Performance Management</p>
        <h2 class="performance-page-title mb-1"><?= performance_escape($title) ?></h2>
        <p class="mb-0 text-sm text-secondary"><?= performance_escape($subtitle) ?></p>
      </div>
      <?php if (!empty($actions)): ?>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($actions as $action): ?>
            <a href="<?= performance_escape($action['href']) ?>" class="btn <?= performance_escape($action['class']) ?> mb-0 <?= !empty($action['modal']) ? 'js-performance-modal' : '' ?>" <?= !empty($action['modal']) ? 'data-modal="' . performance_escape($action['modal']) . '"' : '' ?>>
              <i class="<?= performance_escape($action['icon']) ?> me-2"></i><?= performance_escape($action['label']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
}

function performance_render_nav($menu, $currentView)
{
    ?>
    <div class="performance-nav-wrap mb-4">
      <?php foreach ($menu as $key => $label): ?>
        <a href="?view=<?= performance_escape($key) ?>" class="performance-nav-item <?= $currentView === $key ? 'active' : '' ?>">
          <?= performance_escape($label) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php
}

function performance_render_modal($id, $title, $bodyHtml, $action)
{
    ?>
    <div class="modal fade" id="<?= performance_escape($id) ?>" tabindex="-1" aria-hidden="true" data-create-title="<?= performance_escape($title) ?>">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content performance-modal">
          <div class="modal-header border-0 pb-2">
            <div>
              <p class="performance-eyebrow mb-1">Quick Action</p>
              <h5 class="mb-0 js-performance-modal-title"><?= performance_escape($title) ?></h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form class="modal-body pt-0 js-performance-form" data-action="<?= performance_escape($action) ?>">
            <?= $bodyHtml ?>
            <div class="d-flex justify-content-end gap-2 mt-3">
              <button type="button" class="btn btn-admin-secondary mb-0" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-admin-primary mb-0 js-performance-submit-button">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php
}

function performance_render_edit_modal_button($modalId, $values, $label = 'Edit')
{
    echo '<button type="button" class="btn btn-sm btn-admin-secondary mb-0 js-performance-modal" data-modal="' . performance_escape($modalId) . '" data-edit-title="Edit ' . performance_escape($label) . '" data-form-values="' . performance_json_attr($values) . '"><i class="bi bi-pencil-square me-1"></i>Edit</button>';
}

function performance_render_empty($title, $message)
{
    ?>
    <div class="performance-empty text-center">
      <div class="performance-empty-icon mb-3"><i class="bi bi-inbox-fill"></i></div>
      <h5 class="mb-2"><?= performance_escape($title) ?></h5>
      <p class="text-sm text-secondary mb-0"><?= performance_escape($message) ?></p>
    </div>
    <?php
}
