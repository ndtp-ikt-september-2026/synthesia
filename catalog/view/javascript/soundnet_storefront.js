/**
 * SoundNet Storefront JavaScript Controller
 * Handles AI Mode dynamic modal, Sonic Matching lookups, and carousel navigation
 */

(function(window, document) {
  'use strict';

  // Prevent duplicate execution if script is included more than once
  if (window.SoundnetStorefront && window.SoundnetStorefront.isLoaded) {
    return;
  }

  var SoundnetStorefront = {
    modalId: 'soundnetSimilarModal',
    modalBodyId: 'soundnetSimilarModalContent',
    modalSubtitleId: 'soundnetModalSubtitle',
    isLoaded: true,
    initialized: false,
    isLoading: false,
    activeProductId: null,

    init: function() {
      if (this.initialized) return;
      this.initialized = true;
      var self = this;

      // Initialize and observe mobile cart badge sync
      this.syncCartUI();

      if (window.jQuery) {
        window.jQuery(document).ajaxComplete(function(e, xhr, settings) {
          if (settings && settings.url && (settings.url.indexOf('checkout/cart') !== -1 || settings.url.indexOf('common/cart') !== -1)) {
            setTimeout(function() { self.syncCartUI(); }, 150);
          }
        });
      }

      var cartEl = document.getElementById('cart');
      if (cartEl && window.MutationObserver) {
        var cartObserver = new MutationObserver(function() {
          cartObserver.disconnect();
          self.syncCartUI();
          cartObserver.observe(cartEl, { childList: true, subtree: true, characterData: true });
        });
        cartObserver.observe(cartEl, { childList: true, subtree: true, characterData: true });
      }

      // Event delegation for AI Mode buttons
      document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-ai-mode');
        if (btn) {
          e.preventDefault();
          var productId = btn.getAttribute('data-product-id');
          if (productId) {
            self.openSimilarModal(productId);
          }
        }

        // Mobile filter panel toggle
        var filterHeader = e.target.closest('#column-left .parametric-filter-panel .filter-panel-header');
        if (filterHeader && window.innerWidth <= 991) {
          var panel = filterHeader.closest('.parametric-filter-panel');
          if (panel) {
            panel.classList.toggle('is-open');
          }
        }

        // Close on backdrop click
        if (e.target.classList.contains('soundnet-modal-backdrop')) {
          self.closeSimilarModal();
        }
      });

      // Close on Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
          self.closeSimilarModal();
        }
      });
    },

    syncCartUI: function() {
      var cartBtn = document.querySelector('#cart > .studio-cart-trigger, #cart > button, #cart > .btn');
      if (!cartBtn) return;

      var totalSpan = document.getElementById('cart-total');
      var totalText = totalSpan ? (totalSpan.textContent || '') : '';
      var count = 0;
      var match = totalText.match(/(\d+)/);
      if (match) {
        count = parseInt(match[1], 10) || 0;
      }
      if (totalSpan) {
        totalSpan.classList.add('studio-cart-total-label', 'hidden-xs', 'hidden-sm');
      }

      var badge = cartBtn.querySelector('.studio-cart-count-badge');
      if (!badge) {
        badge = document.createElement('span');
        badge.className = 'studio-cart-count-badge';
        cartBtn.appendChild(badge);
      }
      badge.textContent = count;
      if (count > 0) {
        badge.classList.add('has-items');
      } else {
        badge.classList.remove('has-items');
      }

      var icon = cartBtn.querySelector('.studio-cart-icon');
      if (!icon) {
        var svgWrap = document.createElement('span');
        svgWrap.className = 'studio-cart-icon';
        svgWrap.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>';
        cartBtn.insertBefore(svgWrap, cartBtn.firstChild);
        var oldI = cartBtn.querySelector('i.fa');
        if (oldI) oldI.remove();
      }
    },

    openSimilarModal: function(productId) {
      var self = this;
      var modal = document.getElementById(this.modalId);
      var content = document.getElementById(this.modalBodyId);
      var subtitle = document.getElementById(this.modalSubtitleId);

      if (!modal || !content) return;

      // Prevent duplicate concurrent requests
      if (this.isLoading && this.activeProductId === productId) {
        return;
      }
      this.isLoading = true;
      this.activeProductId = productId;

      // Reset & show loading state
      modal.classList.add('is-active');
      document.body.style.overflow = 'hidden';

      if (subtitle) {
        subtitle.innerHTML = 'Поиск акустических и векторных соответствий...';
      }

      content.innerHTML = 
        '<div class="soundnet-modal-loader">' +
          '<div class="soundnet-loader-spinner"></div>' +
          '<p>Анализ акустических характеристик, частотного профиля и векторного каталога...</p>' +
        '</div>';

      var url = 'index.php?route=extension/module/soundnet_storefront/getSimilar&product_id=' + encodeURIComponent(productId);

      fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(res) {
        if (!res.ok) throw new Error('Network error');
        return res.json();
      })
      .then(function(json) {
        self.isLoading = false;
        if (!json.success || !json.products || json.products.length === 0) {
          content.innerHTML = '<div class="soundnet-modal-loader"><p>По вашему запросу не найдено похожих релизов.</p></div>';
          if (subtitle) {
            subtitle.innerHTML = 'Поиск завершен';
          }
          return;
        }

        if (subtitle && json.product_name) {
          subtitle.innerHTML = 'Релизы, близкие по звучанию и стилю к <strong>«' + escapeHtml(json.product_name) + '»</strong>';
        }

        var html = '<div class="soundnet-modal-grid">';
        for (var i = 0; i < json.products.length; i++) {
          var item = json.products[i];
          html += 
            '<div class="modal-vinyl-card" data-product-id="' + item.product_id + '">' +
              '<div class="modal-vinyl-card__cover-wrap">' +
                '<a href="' + item.href + '">' +
                  '<img src="' + item.thumb + '" alt="' + escapeHtml(item.name) + '" loading="lazy" />' +
                '</a>' +
                (item.format_badge ? '<span class="modal-vinyl-card__badge">' + escapeHtml(item.format_badge) + '</span>' : '') +
              '</div>' +
              '<div class="modal-vinyl-card__body">' +
                '<div class="modal-vinyl-card__artist" title="' + escapeHtml(item.artist) + '">' + escapeHtml(item.artist) + '</div>' +
                '<h4 class="modal-vinyl-card__title"><a href="' + item.href + '" title="' + escapeHtml(item.name) + '">' + escapeHtml(item.name) + '</a></h4>' +
                (item.meta ? '<div class="modal-vinyl-card__meta">' + escapeHtml(item.meta) + '</div>' : '') +
                '<div class="modal-vinyl-card__footer">' +
                  '<span class="modal-vinyl-card__price">' + escapeHtml(item.price) + '</span>' +
                  '<button type="button" class="btn-modal-cart" onclick="cart.add(\'' + item.product_id + '\', 1); SoundnetStorefront.closeSimilarModal();" title="Купить">' +
                    'Купить' +
                  '</button>' +
                '</div>' +
              '</div>' +
            '</div>';
        }
        html += '</div>';

        content.innerHTML = html;
      })
      .catch(function(err) {
        self.isLoading = false;
        content.innerHTML = '<div class="soundnet-modal-loader"><p>Произошла ошибка при загрузке похожих релизов.</p></div>';
      });
    },

    closeSimilarModal: function() {
      var modal = document.getElementById(this.modalId);
      if (modal) {
        modal.classList.remove('is-active');
        document.body.style.overflow = '';
      }
    },

    scrollCarousel: function(containerId, direction) {
      var container = document.getElementById(containerId);
      if (!container) return;
      var card = container.querySelector('.carousel-card-item');
      var scrollAmount = card ? (card.offsetWidth + 12) : (container.clientWidth * 0.75);
      container.scrollBy({
        left: direction === 'next' ? scrollAmount : -scrollAmount,
        behavior: 'smooth'
      });
    }
  };

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  window.SoundnetStorefront = SoundnetStorefront;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      SoundnetStorefront.init();
    });
  } else {
    SoundnetStorefront.init();
  }
})(window, document);
