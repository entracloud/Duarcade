<div class="newsletter-card">
  <div class="newsletter-content">
    <h2>Subscribe to our newsletter</h2>
    <p>Stay updated with our latest news, articles, and exclusive offers delivered straight to your inbox.</p>
    <form id="newsletter-form">
      <div class="input-group">
        <input type="email" id="email" placeholder="Enter your email address" required>
        <button type="submit">Subscribe</button>
      </div>
      <div class="form-feedback" id="form-feedback"></div>
    </form>
    <div class="newsletter-footer">
      <p>We respect your privacy. Unsubscribe at any time.</p>
    </div>
  </div>
</div>

<style>
    .newsletter-card {
  max-width: 450px;
  margin: 0 auto;
  background: #ffffff;
  border-radius: 10px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.newsletter-content {
  padding: 2rem;
}

.newsletter-content h2 {
  margin: 0 0 1rem;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
}

.newsletter-content p {
  margin: 0 0 1.5rem;
  font-size: 0.95rem;
  line-height: 1.5;
  color: #4a5568;
}

.input-group {
  display: flex;
  margin-bottom: 0.5rem;
}

.input-group input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px 0 0 6px;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.2s;
}

.input-group input:focus {
  border-color: #3182ce;
}

.input-group button {
  padding: 0.75rem 1.5rem;
  background: #3182ce;
  color: white;
  font-weight: 600;
  font-size: 0.95rem;
  border: none;
  border-radius: 0 6px 6px 0;
  cursor: pointer;
  transition: background-color 0.2s;
}

.input-group button:hover {
  background: #2c5282;
}

.form-feedback {
  height: 20px;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}

.form-feedback.error {
  color: #e53e3e;
}

.form-feedback.success {
  color: #38a169;
}

.newsletter-footer {
  margin-top: 1rem;
}

.newsletter-footer p {
  font-size: 0.8rem;
  color: #718096;
  margin: 0;
}

@media (max-width: 480px) {
  .input-group {
    flex-direction: column;
  }
  
  .input-group input {
    border-radius: 6px;
    margin-bottom: 0.75rem;
  }
  
  .input-group button {
    border-radius: 6px;
  }
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('newsletter-form');
  const emailInput = document.getElementById('email');
  const feedback = document.getElementById('form-feedback');
  
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = emailInput.value.trim();
    
    if (!isValidEmail(email)) {
      showFeedback('Please enter a valid email address', 'error');
      return;
    }
    
    // This would typically connect to your backend service
    // Simulating API call with timeout
    showFeedback('Subscribing...', '');
    
    setTimeout(() => {
      showFeedback('Thanks for subscribing!', 'success');
      form.reset();
      
      // Reset success message after 3 seconds
      setTimeout(() => {
        feedback.textContent = '';
        feedback.className = 'form-feedback';
      }, 3000);
    }, 1000);
  });
  
  function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  }
  
  function showFeedback(message, type) {
    feedback.textContent = message;
    feedback.className = 'form-feedback';
    if (type) {
      feedback.classList.add(type);
    }
  }
});
</script>