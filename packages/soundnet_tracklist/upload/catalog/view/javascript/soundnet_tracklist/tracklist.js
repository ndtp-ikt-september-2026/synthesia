/**
 * SoundNet Dynamic Tracklist & Audio Player
 * Vanilla JavaScript Audio Engine
 *
 * Architecture: Event-driven singleton audio controller
 * Compatible with OpenCart 3, Turbolinks, and dynamic AJAX tab loaders.
 */

(function () {
  'use strict';

  class SoundNetPlayer {
    constructor() {
      this.audio = new Audio();
      this.audio.preload = 'metadata';

      this.currentTrackIndex = -1;
      this.playlist = [];
      this.isPlaying = false;
      this.isSeeking = false;
      this.autoAdvance = true;
      this.activeWrapper = null;

      this.initVolume();
      this.bindAudioEvents();
      this.bindKeyboardShortcuts();
      this.scanContainers();

      // Auto-scan for dynamic tab switching or AJAX content changes
      document.addEventListener('DOMContentLoaded', () => this.scanContainers());
      window.addEventListener('load', () => this.scanContainers());
    }

    initVolume() {
      const savedVolume = localStorage.getItem('soundnet_player_volume');
      this.audio.volume = savedVolume !== null ? parseFloat(savedVolume) : 0.85;
    }

    scanContainers() {
      const wrappers = document.querySelectorAll('.soundnet-tracklist-wrapper:not([data-soundnet-initialized])');
      wrappers.forEach((wrap) => {
        wrap.setAttribute('data-soundnet-initialized', 'true');
        this.initContainer(wrap);
      });
    }

    initContainer(wrapper) {
      const autoAdvAttr = wrapper.getAttribute('data-auto-advance');
      this.autoAdvance = autoAdvAttr !== '0';

      // Gather tracks inside this wrapper
      const rows = wrapper.querySelectorAll('.soundnet-track-row');
      const containerPlaylist = [];

      rows.forEach((row, index) => {
        const trackId = row.getAttribute('data-track-id');
        const audioUrl = row.getAttribute('data-audio-url');
        const trackNum = row.getAttribute('data-track-num') || (index + 1);
        const title = row.getAttribute('data-title') || ('Track ' + trackNum);
        const duration = row.getAttribute('data-duration') || '0:00';
        const hasPreview = !!audioUrl;

        containerPlaylist.push({
          index: index,
          row: row,
          wrapper: wrapper,
          trackId: trackId,
          audioUrl: audioUrl,
          trackNum: trackNum,
          title: title,
          duration: duration,
          hasPreview: hasPreview
        });

        // Click row play button
        const playBtn = row.querySelector('.js-soundnet-toggle-track');
        if (playBtn && hasPreview) {
          playBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.handleTrackToggle(wrapper, containerPlaylist, index);
          });
        }
      });

      // Quick play all / play first button
      const playAllBtn = wrapper.querySelector('.js-soundnet-play-first');
      if (playAllBtn) {
        playAllBtn.addEventListener('click', () => {
          const firstPlayable = containerPlaylist.findIndex(t => t.hasPreview);
          if (firstPlayable !== -1) {
            this.handleTrackToggle(wrapper, containerPlaylist, firstPlayable);
          }
        });
      }

      // Mini player controls inside wrapper
      const playerBar = wrapper.querySelector('.js-soundnet-player-bar');
      if (playerBar) {
        const mainPlayBtn = playerBar.querySelector('.js-soundnet-main-play');
        if (mainPlayBtn) {
          mainPlayBtn.addEventListener('click', () => this.togglePlayPause());
        }

        const prevBtn = playerBar.querySelector('.js-soundnet-prev');
        if (prevBtn) {
          prevBtn.addEventListener('click', () => this.playPrevious());
        }

        const nextBtn = playerBar.querySelector('.js-soundnet-next');
        if (nextBtn) {
          nextBtn.addEventListener('click', () => this.playNext());
        }

        const closeBtn = playerBar.querySelector('.js-soundnet-player-close');
        if (closeBtn) {
          closeBtn.addEventListener('click', () => {
            this.pause();
            playerBar.classList.add('soundnet-player-closed');
          });
        }

        // Progress bar seeking
        const seekWrap = playerBar.querySelector('.js-soundnet-seek-wrap');
        if (seekWrap) {
          const onSeek = (e) => {
            if (!this.audio.duration) return;
            const rect = seekWrap.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const width = rect.width;
            const pct = Math.max(0, Math.min(1, clickX / width));
            this.audio.currentTime = pct * this.audio.duration;
            this.updateProgressBar(this.audio.currentTime, this.audio.duration);
          };

          seekWrap.addEventListener('mousedown', (e) => {
            this.isSeeking = true;
            onSeek(e);
            const onMouseMove = (moveEvent) => {
              if (this.isSeeking) onSeek(moveEvent);
            };
            const onMouseUp = () => {
              this.isSeeking = false;
              window.removeEventListener('mousemove', onMouseMove);
              window.removeEventListener('mouseup', onMouseUp);
            };
            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
          });

          seekWrap.addEventListener('keydown', (e) => {
            if (!this.audio.duration) return;
            if (e.key === 'ArrowRight') {
              this.audio.currentTime = Math.min(this.audio.duration, this.audio.currentTime + 5);
            } else if (e.key === 'ArrowLeft') {
              this.audio.currentTime = Math.max(0, this.audio.currentTime - 5);
            }
          });
        }

        // Volume control
        const volumeSlider = playerBar.querySelector('.js-soundnet-volume-slider');
        const muteBtn = playerBar.querySelector('.js-soundnet-mute-btn');

        if (volumeSlider) {
          volumeSlider.value = this.audio.volume;
          volumeSlider.addEventListener('input', (e) => {
            const val = parseFloat(e.target.value);
            this.setVolume(val);
          });
        }

        if (muteBtn) {
          muteBtn.addEventListener('click', () => {
            this.toggleMute();
          });
        }
      }
    }

    handleTrackToggle(wrapper, playlist, index) {
      if (this.activeWrapper === wrapper && this.currentTrackIndex === index) {
        this.togglePlayPause();
      } else {
        this.activeWrapper = wrapper;
        this.playlist = playlist;
        this.loadAndPlay(index);
      }
    }

    loadAndPlay(index) {
      if (!this.playlist[index] || !this.playlist[index].hasPreview) {
        return;
      }

      this.currentTrackIndex = index;
      const track = this.playlist[index];

      // Update Audio Source
      this.audio.src = track.audioUrl;
      this.audio.load();

      // Update UI row states
      this.updateRowActiveStates(track);

      // Update Mini Player UI
      this.updatePlayerBarUI(track);

      // Trigger playback
      const playPromise = this.audio.play();
      if (playPromise !== undefined) {
        playPromise.then(() => {
          this.isPlaying = true;
          this.updatePlaybackUI(true);
        }).catch((err) => {
          console.warn('SoundNet Audio play blocked or failed:', err);
          this.isPlaying = false;
          this.updatePlaybackUI(false);
        });
      }
    }

    togglePlayPause() {
      if (!this.audio.src) {
        if (this.playlist.length > 0) {
          const first = this.playlist.findIndex(t => t.hasPreview);
          if (first !== -1) {
            this.loadAndPlay(first);
          }
        }
        return;
      }

      if (this.audio.paused) {
        this.audio.play().then(() => {
          this.isPlaying = true;
          this.updatePlaybackUI(true);
        }).catch(e => console.warn(e));
      } else {
        this.pause();
      }
    }

    pause() {
      this.audio.pause();
      this.isPlaying = false;
      this.updatePlaybackUI(false);
    }

    playNext() {
      if (!this.playlist || this.playlist.length === 0) return;
      let nextIndex = this.currentTrackIndex + 1;
      while (nextIndex < this.playlist.length && !this.playlist[nextIndex].hasPreview) {
        nextIndex++;
      }
      if (nextIndex < this.playlist.length) {
        this.loadAndPlay(nextIndex);
      } else {
        // Loop back or stop
        this.pause();
        this.audio.currentTime = 0;
      }
    }

    playPrevious() {
      if (!this.playlist || this.playlist.length === 0) return;
      if (this.audio.currentTime > 3) {
        this.audio.currentTime = 0;
        return;
      }
      let prevIndex = this.currentTrackIndex - 1;
      while (prevIndex >= 0 && !this.playlist[prevIndex].hasPreview) {
        prevIndex--;
      }
      if (prevIndex >= 0) {
        this.loadAndPlay(prevIndex);
      } else {
        this.audio.currentTime = 0;
      }
    }

    setVolume(value) {
      this.audio.volume = Math.max(0, Math.min(1, value));
      localStorage.setItem('soundnet_player_volume', this.audio.volume);
      this.updateVolumeUI();
    }

    toggleMute() {
      if (this.audio.volume > 0) {
        this.lastVolume = this.audio.volume;
        this.setVolume(0);
      } else {
        this.setVolume(this.lastVolume || 0.85);
      }
    }

    updateVolumeUI() {
      if (!this.activeWrapper) return;
      const playerBar = this.activeWrapper.querySelector('.js-soundnet-player-bar');
      if (!playerBar) return;

      const muteBtn = playerBar.querySelector('.js-soundnet-mute-btn');
      const slider = playerBar.querySelector('.js-soundnet-volume-slider');

      if (slider) slider.value = this.audio.volume;
      if (muteBtn) {
        if (this.audio.volume === 0) {
          muteBtn.classList.add('is-muted');
        } else {
          muteBtn.classList.remove('is-muted');
        }
      }
    }

    updateRowActiveStates(activeTrack) {
      if (!this.activeWrapper) return;
      const rows = this.activeWrapper.querySelectorAll('.soundnet-track-row');
      rows.forEach((r) => {
        r.classList.remove('is-playing', 'is-audio-active');
      });

      if (activeTrack && activeTrack.row) {
        activeTrack.row.classList.add('is-playing', 'is-audio-active');
      }
    }

    updatePlayerBarUI(track) {
      if (!this.activeWrapper) return;
      const playerBar = this.activeWrapper.querySelector('.js-soundnet-player-bar');
      if (!playerBar) return;

      playerBar.classList.remove('soundnet-player-idle', 'soundnet-player-closed');

      const titleEl = playerBar.querySelector('.js-soundnet-player-title');
      if (titleEl) {
        titleEl.textContent = track.title;
      }

      const totalTimeEl = playerBar.querySelector('.js-soundnet-total-time');
      if (totalTimeEl) {
        totalTimeEl.textContent = track.duration || '0:00';
      }

      this.updateVolumeUI();
    }

    updatePlaybackUI(isPlaying) {
      if (!this.activeWrapper) return;
      const playerBar = this.activeWrapper.querySelector('.js-soundnet-player-bar');
      const currentTrack = this.playlist[this.currentTrackIndex];

      if (isPlaying) {
        this.activeWrapper.classList.add('is-playing');
        if (playerBar) playerBar.classList.add('is-playing');
        if (currentTrack && currentTrack.row) {
          currentTrack.row.classList.add('is-audio-active');
        }
      } else {
        this.activeWrapper.classList.remove('is-playing');
        if (playerBar) playerBar.classList.remove('is-playing');
        if (currentTrack && currentTrack.row) {
          currentTrack.row.classList.remove('is-audio-active');
        }
      }
    }

    updateProgressBar(current, duration) {
      if (!this.activeWrapper) return;
      const playerBar = this.activeWrapper.querySelector('.js-soundnet-player-bar');
      if (!playerBar) return;

      const progressEl = playerBar.querySelector('.js-soundnet-progress-bar');
      const currentTimeEl = playerBar.querySelector('.js-soundnet-current-time');
      const totalTimeEl = playerBar.querySelector('.js-soundnet-total-time');
      const seekWrap = playerBar.querySelector('.js-soundnet-seek-wrap');

      if (!duration || isNaN(duration)) {
        duration = 0;
      }

      const pct = duration > 0 ? (current / duration) * 100 : 0;
      if (progressEl) progressEl.style.width = pct + '%';
      if (seekWrap) seekWrap.setAttribute('aria-valuenow', Math.round(pct));
      if (currentTimeEl) currentTimeEl.textContent = this.formatTime(current);
      if (totalTimeEl && duration > 0) totalTimeEl.textContent = this.formatTime(duration);
    }

    formatTime(seconds) {
      if (isNaN(seconds) || seconds < 0) return '0:00';
      const mins = Math.floor(seconds / 60);
      const secs = Math.floor(seconds % 60);
      return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }

    bindAudioEvents() {
      this.audio.addEventListener('timeupdate', () => {
        if (!this.isSeeking) {
          this.updateProgressBar(this.audio.currentTime, this.audio.duration);
        }
      });

      this.audio.addEventListener('progress', () => {
        if (!this.activeWrapper || !this.audio.duration) return;
        const playerBar = this.activeWrapper.querySelector('.js-soundnet-player-bar');
        if (!playerBar) return;

        const loadedBar = playerBar.querySelector('.js-soundnet-progress-loaded');
        if (loadedBar && this.audio.buffered.length > 0) {
          const bufferedEnd = this.audio.buffered.end(this.audio.buffered.length - 1);
          const pct = (bufferedEnd / this.audio.duration) * 100;
          loadedBar.style.width = Math.min(100, pct) + '%';
        }
      });

      this.audio.addEventListener('ended', () => {
        if (this.autoAdvance) {
          this.playNext();
        } else {
          this.pause();
        }
      });

      this.audio.addEventListener('error', (e) => {
        console.warn('SoundNet Audio error:', e);
        this.pause();
      });
    }

    bindKeyboardShortcuts() {
      window.addEventListener('keydown', (e) => {
        // Prevent interfering with input typing
        const tag = e.target.tagName ? e.target.tagName.toLowerCase() : '';
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) {
          return;
        }

        if (e.code === 'Space') {
          if (this.activeWrapper && this.audio.src) {
            e.preventDefault();
            this.togglePlayPause();
          }
        } else if (e.shiftKey && e.key.toLowerCase() === 'n') {
          this.playNext();
        } else if (e.shiftKey && e.key.toLowerCase() === 'p') {
          this.playPrevious();
        }
      });
    }
  }

  // Instantiate Global Audio Controller
  if (!window.SoundNetAudio) {
    window.SoundNetAudio = new SoundNetPlayer();
  }
})();
