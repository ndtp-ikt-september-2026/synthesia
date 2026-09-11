/**
 * SoundNet Storefront JavaScript Controller
 * Handles AI Mode dynamic modal, Sonic Matching lookups, and carousel navigation
 */

(function(window, document) {
  'use strict';

  var SoundnetStorefront = {
    modalId: 'soundnetSimilarModal',
    modalBodyId: 'soundnetSimilarModalContent',
    modalSubtitleId: 'soundnetModalSubtitle',

    init: function() {
      var self = this;

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

    openSimilarModal: function(productId) {
      var modal = document.getElementById(this.modalId);
      var content = document.getElementById(this.modalBodyId);
      var subtitle = document.getElementById(this.modalSubtitleId);

      if (!modal || !content) return;

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
      var scrollAmount = container.clientWidth * 0.75;
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
