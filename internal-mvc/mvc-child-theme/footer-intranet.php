</div><!-- .site-content -->
    </main>

    <!-- Custom MVC Intranet Footer -->
    <footer class="mvc-intranet-footer">
        <div class="mvc-footer-content">
            <p>&copy; <?php echo date('Y'); ?> Media Vines Corp. All rights reserved.</p>
        </div>
    </footer>

    <style>
        /* Streamlined Footer Styles */
        .mvc-intranet-footer {
            background: #000;
            padding: 20px;
            text-align: center;
            border-top: 2px solid #D4AF37;
            margin-top: 0;
        }

        .mvc-footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .mvc-footer-content p {
            margin: 0;
            color: #D4AF37;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mvc-intranet-footer {
                padding: 15px;
            }
            
            .mvc-footer-content p {
                font-size: 12px;
            }
        }
    </style>

    <?php wp_footer(); ?>
</body>
</html>