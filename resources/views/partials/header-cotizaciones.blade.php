<script>
    (function () {
        'use strict';
        let isOpen = false;
        let ratesLoaded = false;

        window.toggleCotizDropdown = function () {
            const dropdown = document.getElementById('cotizDropdown');
            isOpen = !isOpen;
            dropdown.style.display = isOpen ? 'block' : 'none';
            if (isOpen && !ratesLoaded) {
                fetchRates();
            }
        };

        function fetchRates() {
            fetch('{{ route("api.cotizaciones.today") }}')
                .then(r => r.json())
                .then(data => {
                    const content = document.getElementById('cotizContent');
                    content.innerHTML = `
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <img src="https://flagcdn.com/w20/py.png" class="w-4 h-3 rounded-sm object-cover" alt="Paraguay">
                                <span class="text-sm font-medium">Guaraní (PYG)</span>
                            </div>
                            <span class="text-sm font-bold text-accent">${formatNumber(data.PYG || 0, 0)}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <img src="https://flagcdn.com/w20/br.png" class="w-4 h-3 rounded-sm object-cover" alt="Brasil">
                                <span class="text-sm font-medium">Real (BRL)</span>
                            </div>
                            <span class="text-sm font-bold text-accent">${formatNumber(data.BRL || 0, 2)}</span>
                        </div>
                    `;
                    ratesLoaded = true;
                })
                .catch(err => {
                    console.error('Error fetching rates:', err);
                    document.getElementById('cotizContent').innerHTML = '<span class="text-xs text-red-400">Error al cargar cotizaciones</span>';
                });
        }

        document.addEventListener('click', function (e) {
            const wrapper = document.getElementById('cotizWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                const dropdown = document.getElementById('cotizDropdown');
                if (dropdown) dropdown.style.display = 'none';
                isOpen = false;
            }
        });

        function formatNumber(n, decimals = 0) {
            return parseFloat(n).toLocaleString('de-DE', { 
                minimumFractionDigits: decimals, 
                maximumFractionDigits: decimals 
            });
        }
    })();
</script>
