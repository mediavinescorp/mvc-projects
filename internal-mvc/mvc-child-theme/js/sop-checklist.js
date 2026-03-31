function toggleSection(header) {
  var section = header.closest('.section');
  section.classList.toggle('open');
}

function toggleCheck(label) {
  var cb = label.querySelector('input[type="checkbox"]');
  cb.checked = !cb.checked;
  label.classList.toggle('checked', cb.checked);
  updateProgress();
  checkSectionComplete(label.closest('.section'));
}

function checkSectionComplete(section) {
  if (!section) return;
  var items = section.querySelectorAll('.check-item');
  var checked = section.querySelectorAll('.check-item.checked');
  if (items.length > 0 && items.length === checked.length) {
    section.classList.add('all-done');
  } else {
    section.classList.remove('all-done');
  }
}

function updateProgress() {
  var all = document.querySelectorAll('.check-item');
  var done = document.querySelectorAll('.check-item.checked');
  var pct = all.length ? Math.round((done.length / all.length) * 100) : 0;
  document.getElementById('progressBar').style.width = pct + '%';
  document.getElementById('progressCount').textContent = done.length + ' / ' + all.length;
}

function resetAll() {
  document.querySelectorAll('.check-item').forEach(function(item) {
    item.classList.remove('checked');
    item.querySelector('input').checked = false;
  });
  document.querySelectorAll('.section').forEach(function(s) {
    s.classList.remove('all-done');
  });
  updateProgress();
}

document.addEventListener('DOMContentLoaded', function() {
  updateProgress();
});
