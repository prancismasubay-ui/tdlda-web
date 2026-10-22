<dialog id="wordFeedbackDialog" class="word-feedback-dialog" aria-labelledby="wordFeedbackTitle">
  <h2 id="wordFeedbackTitle">Feedback on this word</h2>
  <p id="feedbackWordLabel"></p>
  <form id="wordFeedbackForm" method="post" action="process/feedback_save.php">
    <input type="hidden" name="word_id">
    <div class="form-group">
      <label for="feedbackType">Feedback type</label>
      <select id="feedbackType" name="feedback_type" required>
        <option value="Comment">General comment</option>
        <option value="Spelling">Spelling may be incorrect</option>
        <option value="Definition">Definition may be incorrect</option>
        <option value="Example">Example sentence needs correction</option>
        <option value="Suggestion">Suggestion for this entry</option>
      </select>
    </div>
    <div class="form-group">
      <label for="feedbackRating">Rating</label>
      <select id="feedbackRating" name="rating" required>
        <option value="5">5 - Very satisfied</option>
        <option value="4">4 - Satisfied</option>
        <option value="3">3 - Neutral</option>
        <option value="2">2 - Dissatisfied</option>
        <option value="1">1 - Very dissatisfied</option>
      </select>
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label for="feedbackName">Your name (optional)</label>
        <input id="feedbackName" name="respondent_name" type="text" maxlength="100" autocomplete="name">
      </div>
      <div class="form-group">
        <label for="feedbackRespondentType">Respondent type</label>
        <select id="feedbackRespondentType" name="respondent_type">
          <option value="Other">Other</option>
          <option value="Tourist">Tourist</option>
          <option value="Non-Kalinga Speaker">Non-native speaker</option>
          <option value="Native Speaker">Native speaker</option>
          <option value="Student">Student</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label for="feedbackComments">Comments</label>
      <textarea id="feedbackComments" name="comments" maxlength="2000" required rows="4"></textarea>
    </div>
    <p id="wordFeedbackStatus" role="status" aria-live="polite" hidden></p>
    <div class="modal-actions">
      <button type="button" class="btn btn-outline" id="closeWordFeedback">Cancel</button>
      <button type="submit" class="btn btn-primary">Submit feedback</button>
    </div>
  </form>
</dialog>
