        </section>
    </main>
</div>
<script>
const botaoMenu = document.querySelector('[data-admin-menu]');
const sidebar = document.querySelector('.admin-sidebar');
if (botaoMenu && sidebar) {
    botaoMenu.addEventListener('click', () => sidebar.classList.toggle('aberto'));
}
</script>
</body>
</html>
