async function postForm(url, data) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams(data)
  });

  return response.json();
}

function setMessage(el, text, isSuccess = false) {
  if (!el) return;
  el.textContent = text;
  el.classList.toggle('success', isSuccess);
  el.classList.toggle('error', !isSuccess);
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// main page
const recentReviewsBox = document.getElementById('recentReviews');
if (recentReviewsBox) {
  fetch('api/recent_reviews.php')
    .then((response) => response.json())
    .then((result) => {
      if (!result.success || !result.reviews.length) {
        recentReviewsBox.textContent = '< entry > — no reviews found yet';
        return;
      }

      recentReviewsBox.innerHTML = result.reviews
        .map((review) => `
          <div class="recent-review-item">
            <span class="recent-review-title">${escapeHtml(review.title)}</span>
            <span>User ${escapeHtml(review.userid)} · ${escapeHtml(review.rating)}/5 · ${escapeHtml(review.verdict || 'No tag')}</span>
          </div>
        `)
        .join('');
    })
    .catch(() => {
      recentReviewsBox.textContent = '< entry > — could not load reviews';
    });
}

// date entry page
const entryForm = document.getElementById('entryForm');
if (entryForm) {
  const movieInput = document.getElementById('movie');
  const genreInput = document.getElementById('genre');
  const entryMessage = document.getElementById('entryMessage');

  async function autoFillGenre() {
    const title = movieInput.value.trim();
    if (!title) return;
    try {
      const result = await postForm('api/find_movie.php', { movie_title: title });
      if (result.success) {
        genreInput.value = result.movie.genres || '';
        setMessage(entryMessage, `Matched movie: ${result.movie.title}`, true);
      }
    } catch (_) {
      setMessage(entryMessage, 'Could not auto-fill genre.');
    }
  }

  movieInput.addEventListener('blur', autoFillGenre);

  entryForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = {
      reviewer_name: document.getElementById('username').value.trim(),
      movie_title: document.getElementById('movie').value.trim(),
      genre: document.getElementById('genre').value.trim(),
      rating: document.getElementById('rating').value.trim(),
      verdict: document.getElementById('description').value.trim()
    };

    try {
      const result = await postForm('api/add_review.php', formData);
      setMessage(entryMessage, result.message + (result.movie ? ` Genre loaded: ${result.movie.genres}` : ''), !!result.success);
      if (result.success) {
        document.getElementById('genre').value = result.movie.genres || document.getElementById('genre').value;
      }
    } catch (error) {
      setMessage(entryMessage, 'Something went wrong while saving the review.');
    }
  });
}

// delete data page
function renderDeleteCard(review) {
  return `
    <div class="result-item" data-userid="${escapeHtml(review.userid)}" data-movieid="${escapeHtml(review.movieid)}">
      <div class="icon-box">×</div>
      <div>
        <div class="result-meta">
          <div class="result-meta-item"><span class="result-meta-label">Reviewer</span><span class="result-meta-value">User ${escapeHtml(review.userid)}</span></div>
          <div class="result-meta-item"><span class="result-meta-label">Movie</span><span class="result-meta-value">${escapeHtml(review.movie_title)}</span></div>
          <div class="result-meta-item"><span class="result-meta-label">Genre</span><span class="result-meta-value">${escapeHtml(review.genre || '—')}</span></div>
          <div class="result-meta-item"><span class="result-meta-label">Rating</span><span class="result-meta-value">${escapeHtml(review.rating)} / 5</span></div>
          <div class="result-meta-item"><span class="result-meta-label">Verdict</span><span class="result-meta-value">${escapeHtml(review.verdict || '—')}</span></div>
        </div>
        <div class="popup-demo confirm-box">
          <h4>Confirm Deletion</h4>
          <p>Are you sure you want to permanently remove this review? This action cannot be undone.</p>
          <div class="popup-buttons">
            <button class="btn btn-danger confirm-delete" type="button">Yes, delete</button>
            <button class="btn btn-ghost cancel-delete" type="button">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

const deleteForm = document.getElementById('deleteSearchForm');
if (deleteForm) {
  const resultsBox = document.getElementById('deleteResults');
  const messageBox = document.getElementById('deleteMessage');

  deleteForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    resultsBox.innerHTML = '';
    setMessage(messageBox, 'Searching...', true);

    try {
      const result = await postForm('api/search_reviews.php', {
        reviewer_name: document.getElementById('delete-user').value.trim(),
        movie_title: document.getElementById('delete-movie').value.trim()
      });

      if (!result.success || !result.reviews.length) {
        setMessage(messageBox, result.message || 'No matching reviews found.');
        return;
      }

      setMessage(messageBox, result.message, true);
      resultsBox.innerHTML = result.reviews.map(renderDeleteCard).join('');
    } catch (_) {
      setMessage(messageBox, 'Search failed.');
    }
  });

  resultsBox.addEventListener('click', async (event) => {
    const card = event.target.closest('.result-item');
    if (!card) return;

    if (event.target.classList.contains('cancel-delete')) {
      card.remove();
      return;
    }

    if (event.target.classList.contains('confirm-delete')) {
      try {
        const result = await postForm('api/delete_review.php', {
          userid: card.dataset.userid,
          movieid: card.dataset.movieid
        });
        setMessage(messageBox, result.message, !!result.success);
        if (result.success) card.remove();
      } catch (_) {
        setMessage(messageBox, 'Delete failed.');
      }
    }
  });
}

// update data page
function renderUpdateCard(review) {
  return `
    <div class="result-item" data-userid="${escapeHtml(review.userid)}" data-movieid="${escapeHtml(review.movieid)}">
      <div class="icon-box edit">✎</div>
      <div>
        <span class="clickable-value">Reviewer: User ${escapeHtml(review.userid)}</span>
        <span class="clickable-value">Movie: ${escapeHtml(review.movie_title)}</span>
        <span class="clickable-value">Genre: ${escapeHtml(review.genre || '—')}</span>
        <span class="clickable-value">Rating: ${escapeHtml(review.rating)}/5</span>
        <span class="clickable-value">Verdict: ${escapeHtml(review.verdict || '—')}</span>
        <div class="popup-demo update-box">
          <h4>Edit Review</h4>
          <div class="form-group" style="margin-top:6px;">
            <label>New Rating</label>
            <input type="text" class="update-rating" placeholder="Type updated rating here" value="${escapeHtml(review.rating)}">
          </div>
          <div class="form-group" style="margin-top:6px;">
            <label>New Verdict / Tag</label>
            <input type="text" class="update-verdict" placeholder="Type updated verdict here" value="${escapeHtml(review.verdict || '')}">
          </div>
          <div class="popup-buttons">
            <button class="btn btn-primary save-update" type="button">Save Change</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

const updateForm = document.getElementById('updateSearchForm');
if (updateForm) {
  const resultsBox = document.getElementById('updateResults');
  const messageBox = document.getElementById('updateMessage');

  updateForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    resultsBox.innerHTML = '';
    setMessage(messageBox, 'Searching...', true);

    try {
      const result = await postForm('api/search_reviews.php', {
        reviewer_name: document.getElementById('update-user').value.trim(),
        movie_title: document.getElementById('update-movie').value.trim()
      });

      if (!result.success || !result.reviews.length) {
        setMessage(messageBox, result.message || 'No matching reviews found.');
        return;
      }

      setMessage(messageBox, result.message, true);
      resultsBox.innerHTML = result.reviews.map(renderUpdateCard).join('');
    } catch (_) {
      setMessage(messageBox, 'Search failed.');
    }
  });

  resultsBox.addEventListener('click', async (event) => {
    if (!event.target.classList.contains('save-update')) return;

    const card = event.target.closest('.result-item');
    const rating = card.querySelector('.update-rating').value.trim();
    const verdict = card.querySelector('.update-verdict').value.trim();

    try {
      const result = await postForm('api/update_review.php', {
        userid: card.dataset.userid,
        movieid: card.dataset.movieid,
        rating,
        verdict
      });
      setMessage(messageBox, result.message, !!result.success);
    } catch (_) {
      setMessage(messageBox, 'Update failed.');
    }
  });
}

// summary page
const summaryData = document.getElementById('summaryData');
if (summaryData) {
  fetch('api/get_summary.php')
    .then((response) => response.json())
    .then((result) => {
      if (!result.success) {
        summaryData.innerHTML = '<div class="note error">Could not load summary data.</div>';
        return;
      }

      document.getElementById('totalReviews').textContent = result.summary.total_reviews;
      document.getElementById('uniqueMovies').textContent = result.summary.unique_movies;
      document.getElementById('avgRating').textContent = result.summary.avg_rating;
      document.getElementById('reviewerCount').textContent = result.summary.reviewers;

      summaryData.innerHTML = result.top_movies.map((movie) => `
        <div class="summary-row">
          <strong>${escapeHtml(movie.title)}</strong>
          <span>${escapeHtml(movie.genres || '—')}</span>
          <span>Avg Rating: ${escapeHtml(movie.avg_rating)}</span>
          <span>Reviews: ${escapeHtml(movie.review_count)}</span>
        </div>
      `).join('');
    })
    .catch(() => {
      summaryData.innerHTML = '<div class="note error">Could not load summary data.</div>';
    });
}
