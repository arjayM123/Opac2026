
    <style>
        /* Reset styles */

        
        /* Footer styles */
        .footer {

            color:rgb(251, 251, 251);
            width: 100%;
        }

        .footer-container {
            text-align: center;
            padding: 20px;
            background-color: #006747;
        }
        
        
        /* Responsive styles */
        @media screen and (max-width: 768px) {
            .footer-section {
                flex: 100%;
            }
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    
    <!-- Footer section -->
    <footer class="footer">
        <div class="footer-container">
            <p>© <span id="current-year"></span> Isabela State University - Roxas Library OPAC. All Rights Reserved.</p>

        </div>
    </footer>
    
    <script>
        document.getElementById('current-year').textContent = new Date().getFullYear();
    </script>
