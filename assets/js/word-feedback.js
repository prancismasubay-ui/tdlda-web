const feedbackDialog = document.getElementById('wordFeedbackDialog');
const feedbackForm = document.getElementById('wordFeedbackForm');
const feedbackStatus = document.getElementById('wordFeedbackStatus');
const feedbackSubmit = feedbackForm.querySelector('[type="submit"]');
const feedbackClose = document.getElementById('closeWordFeedback');
let feedbackSession = 0;

document.addEventListener('click', event => {
  const button = event.target.closest('[data-feedback-word-id]');
  if (!button) return;
  feedbackSession++;
  feedbackForm.reset();
  feedbackForm.elements.word_id.value = button.dataset.feedbackWordId;
  document.getElementById('feedbackWordLabel').textContent =
    [button.dataset.feedbackTerm, button.dataset.feedbackDialect].filter(Boolean).join(' - ');
  feedbackStatus.hidden = true;
  feedbackSubmit.disabled = false;
  feedbackSubmit.textContent = 'Submit feedback';
  feedbackClose.textContent = 'Cancel';
  feedbackDialog.showModal();
});
feedbackClose.addEventListener('click', () => feedbackDialog.close());
feedbackDialog.addEventListener('close', () => { feedbackSession++; });

feedbackForm.addEventListener('submit', async event => {
  event.preventDefault();
  if (feedbackSubmit.disabled || !feedbackForm.reportValidity()) return;
  const session = feedbackSession;
  feedbackSubmit.disabled = true;
  feedbackSubmit.textContent = 'Submitting...';
  feedbackStatus.hidden = true;
  try {
    const response = await fetch(feedbackForm.action, { method: 'POST', body: new FormData(feedbackForm) });
    const result = await response.json();
    if (session !== feedbackSession) return;
    if (!response.ok || !result.success) throw new Error(result.message || 'Feedback could not be submitted.');
    feedbackStatus.textContent = result.message;
    feedbackStatus.hidden = false;
    feedbackSubmit.textContent = 'Submitted';
    feedbackClose.textContent = 'Close';
  } catch (error) {
    if (session !== feedbackSession) return;
    feedbackStatus.textContent = error instanceof TypeError ?
      'Could not connect. Your feedback has not been submitted. Please try again.' : error.message;
    feedbackStatus.hidden = false;
    feedbackSubmit.disabled = false;
    feedbackSubmit.textContent = 'Submit feedback';
  }
});
