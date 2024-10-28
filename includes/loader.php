  <!-- Embedded CSS for the Loader and Content -->
  <style>
      /* Loader overlay */
      .loader-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(255, 255, 255, 0.8);
          display: flex;
          justify-content: center;
          align-items: center;
          z-index: 9999;
          /* Ensure it's on top of other elements */
      }

      /* Spinner */
      .spinner {
          border: 8px solid rgba(0, 0, 0, 0.1);
          /* Light grey background */
          border-top: 8px solid #3498db;
          /* Blue spinner */
          border-radius: 50%;
          width: 60px;
          height: 60px;
          animation: spin 1s linear infinite;
      }

      /* Spin animation */
      @keyframes spin {
          0% {
              transform: rotate(0deg);
          }

          100% {
              transform: rotate(360deg);
          }
      }

      /* Hide content while loading */
      #content {
          display: none;
      }
  </style>

  <!-- Loader Component -->
  <div id="loader" class="loader-overlay">
      <div class="spinner"></div>
  </div>


  <script>
      document.addEventListener("DOMContentLoaded", function() {
          // DOM is fully loaded, but external resources like images might still be loading
          const loader = document.getElementById('loader');
          const content = document.getElementsByTagName('body');

          // Once the whole page (including images) is fully loaded, hide the loader and show the content
          window.addEventListener('load', function() {
              loader.style.display = 'none'; // Hide the loader
              content.style.display = 'block'; // Show the content
          });
      });
  </script>