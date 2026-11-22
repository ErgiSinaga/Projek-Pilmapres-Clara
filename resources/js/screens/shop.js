import { api } from '../api.js';

export async function createShopScreen(container, router) {
  let shopData = null;

  container.innerHTML = `
    <div style="min-height: 100vh; background: #F8FAFC; padding: 28px 16px; box-sizing: border-box;">
      <div style="max-width: 800px; margin: 0 auto;">

        <!-- Header Toko -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: #fff; padding: 16px 24px; border-radius: 18px; border: 2px solid #E8F6FB; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
          <div>
            <h1 style="color: var(--color-title); font-size: 26px; font-weight: 800; margin: 0 0 4px 0;">Toko Aksesori Dento</h1>
            <p style="color: #64748B; font-size: 14px; margin: 0;">Gunakan koin hasil belajarmu untuk menghias Dento!</p>
          </div>
          <div style="display: flex; align-items: center; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 6px; background: #FFFBEB; border: 1.5px solid #FCD34D; padding: 8px 16px; border-radius: 20px;">
              <span style="font-size: 20px;">🪙</span>
              <span id="shop-coin-display" style="font-size: 20px; font-weight: 800; color: #B45309;">0</span>
            </div>
            <button id="btn-back-home" style="min-height: 44px; padding: 10px 18px; background: var(--color-sky-dark); color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">
              &larr; Rumah
            </button>
          </div>
        </div>

        <!-- Grid Item Toko -->
        <div id="shop-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 18px;">
          <p style="text-align: center; color: #64748B;">Memuat aksesori...</p>
        </div>

      </div>
    </div>
  `;

  async function loadShop() {
    try {
      shopData = await api.get('/api/shop');
      container.querySelector('#shop-coin-display').textContent = shopData.koin;
      renderItems();
    } catch (e) {
      container.querySelector('#shop-grid').innerHTML = `<p style="color:red;">Gagal memuat toko.</p>`;
    }
  }

  function renderItems() {
    const grid = container.querySelector('#shop-grid');
    grid.innerHTML = shopData.items.map(item => `
      <div style="background: #ffffff; padding: 20px; border-radius: 18px; border: 2px solid ${item.is_equipped ? 'var(--color-sky)' : '#E2E8F0'}; box-shadow: 0 4px 10px rgba(0,0,0,0.02); display: flex; flex-direction: column; align-items: center; text-align: center;">

        <div style="font-size: 11px; font-weight: 800; color: #64748B; text-transform: uppercase; margin-bottom: 8px;">
          Slot: ${item.slot}
        </div>

        <div style="width: 80px; height: 80px; border-radius: 16px; background: #E8F6FB; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; font-size: 32px;">
          ${item.slot === 'topi' ? '🎩' : (item.slot === 'kacamata' ? '👓' : (item.slot === 'jubah' ? '🦸' : '🖼️'))}
        </div>

        <div style="font-size: 17px; font-weight: 800; color: var(--color-navy); margin-bottom: 4px;">
          ${item.nama}
        </div>

        <div style="font-size: 15px; font-weight: 700; color: #B45309; margin-bottom: 16px;">
          🪙 ${item.harga} Koin
        </div>

        <div style="width: 100%; margin-top: auto;">
          ${!item.is_owned ? `
            <button class="buy-btn" data-id="${item.id}"
              style="min-height: 48px; width: 100%; background: #059669; color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer;">
              Beli Aksesori
            </button>
          ` : (item.is_equipped ? `
            <button class="unequip-btn" data-slot="${item.slot}"
              style="min-height: 48px; width: 100%; background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer;">
              ✓ Terpasang (Lepas)
            </button>
          ` : `
            <button class="equip-btn" data-slot="${item.slot}" data-id="${item.id}"
              style="min-height: 48px; width: 100%; background: var(--color-sky-dark); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer;">
              Pasang
            </button>
          `)}
        </div>
      </div>
    `).join('');

    // Event listeners
    grid.querySelectorAll('.buy-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = parseInt(btn.dataset.id, 10);
        btn.disabled = true;
        btn.textContent = 'Membeli...';
        try {
          const res = await api.post('/api/shop/buy', { item_id: id });
          alert(res.message);
          await loadShop();
        } catch (err) {
          alert(err.message || 'Gagal membeli item.');
          btn.disabled = false;
          btn.textContent = 'Beli Aksesori';
        }
      });
    });

    grid.querySelectorAll('.equip-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const slot = btn.dataset.slot;
        const itemId = parseInt(btn.dataset.id, 10);
        try {
          await api.post('/api/pet/equip', { slot, item_id: itemId });
          await loadShop();
        } catch (err) {
          alert(err.message || 'Gagal memasang aksesori.');
        }
      });
    });

    grid.querySelectorAll('.unequip-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const slot = btn.dataset.slot;
        try {
          await api.post('/api/pet/equip', { slot, item_id: null });
          await loadShop();
        } catch (err) {
          alert(err.message || 'Gagal melepas aksesori.');
        }
      });
    });
  }

  container.querySelector('#btn-back-home').addEventListener('click', () => {
    router.navigate('home');
  });

  loadShop();

  return {};
}
