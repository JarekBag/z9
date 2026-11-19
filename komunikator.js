(function () {
    'use strict';

    const form = document.getElementById('messageForm');
    const feed = document.getElementById('messageFeed');
    const sendStatus = document.getElementById('sendStatus');
    const loadStatus = document.getElementById('loadStatus');
    const sendButton = document.getElementById('sendButton');
    const isAdmin = feed && feed.dataset.isAdmin === '1';
    let loading = false;

    if (!form || !feed) {
        return;
    }

    function setStatus(element, message, isError) {
        element.textContent = message;
        element.classList.toggle('text-danger', Boolean(isError));
        element.classList.toggle('text-muted', !isError);
    }

    async function readJson(response) {
        try {
            return await response.json();
        } catch (error) {
            return { error: 'Serwer zwrócił nieprawidłową odpowiedź (' + response.status + ').' };
        }
    }

    function createMessage(message) {
        const article = document.createElement('article');
        article.className = 'message-bubble';
        article.dataset.messageId = String(message.idk);

        let hash = 0;
        for (let index = 0; index < message.user.length; index += 1) {
            hash = ((hash * 31) + message.user.charCodeAt(index)) >>> 0;
        }
        article.style.setProperty('--user-color', 'hsl(' + (hash % 360) + ', 68%, 42%)');

        const header = document.createElement('div');
        header.className = 'message-header';
        header.textContent = message.user + ' → ' + message.recipient + ' · ' + message.datetime;
        article.appendChild(header);

        if (message.type === 'file' && message.file) {
            const file = message.file;
            if (file.caption) {
                const caption = document.createElement('p');
                caption.className = 'message-content mb-2 mt-2';
                caption.textContent = file.caption;
                article.appendChild(caption);
            }

            if (file.kind === 'image') {
                const image = document.createElement('img');
                image.className = 'message-media message-media-image';
                image.src = file.url;
                image.alt = file.name;
                image.loading = 'lazy';
                article.appendChild(image);
            } else if (file.kind === 'video') {
                const video = document.createElement('video');
                video.className = 'message-media';
                video.src = file.url;
                video.autoplay = true;
                video.muted = true;
                video.controls = true;
                video.preload = 'metadata';
                video.style.maxWidth = '320px';
                article.appendChild(video);
            } else if (file.kind === 'audio') {
                const audio = document.createElement('audio');
                audio.className = 'message-media';
                audio.src = file.url;
                audio.controls = true;
                audio.preload = 'metadata';
                article.appendChild(audio);
            } else {
                const link = document.createElement('a');
                link.href = file.url;
                link.textContent = '📎 ' + file.name;
                if (file.kind === 'pdf') {
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                } else {
                    link.download = file.name;
                }
                article.appendChild(link);
            }
        } else {
            const content = document.createElement('p');
            content.className = 'message-content mb-0 mt-2';
            content.textContent = message.text;
            article.appendChild(content);

            if ('speechSynthesis' in window && message.text) {
                const controls = document.createElement('div');
                controls.className = 'message-controls';
                const speakButton = document.createElement('button');
                speakButton.className = 'btn btn-sm btn-outline-secondary';
                speakButton.type = 'button';
                speakButton.textContent = 'Odczytaj';
                speakButton.addEventListener('click', function () {
                    window.speechSynthesis.cancel();
                    const utterance = new SpeechSynthesisUtterance(message.text);
                    utterance.lang = 'pl-PL';
                    window.speechSynthesis.speak(utterance);
                });
                controls.appendChild(speakButton);
                article.appendChild(controls);
            }
        }

        if (isAdmin) {
            let controls = article.querySelector('.message-controls');
            if (!controls) {
                controls = document.createElement('div');
                controls.className = 'message-controls';
                article.appendChild(controls);
            }
            const deleteButton = document.createElement('button');
            deleteButton.className = 'btn btn-sm btn-outline-danger';
            deleteButton.type = 'button';
            deleteButton.textContent = 'Usuń';
            deleteButton.addEventListener('click', function () {
                deleteMessage(message.idk, article, deleteButton);
            });
            controls.appendChild(deleteButton);
        }

        return article;
    }

    function updateFeed(messages) {
        const placeholder = document.getElementById('feedPlaceholder');
        if (placeholder) {
            placeholder.remove();
        }

        const activeIds = new Set(messages.map(function (message) {
            return String(message.idk);
        }));
        const existingIds = new Set();
        feed.querySelectorAll('[data-message-id]').forEach(function (element) {
            const id = element.dataset.messageId;
            existingIds.add(id);
            if (!activeIds.has(id)) {
                element.remove();
            }
        });

        const newMessages = document.createDocumentFragment();
        messages.forEach(function (message) {
            if (!existingIds.has(String(message.idk))) {
                newMessages.appendChild(createMessage(message));
            }
        });
        if (newMessages.childNodes.length > 0) {
            feed.insertBefore(newMessages, feed.firstChild);
        }

        if (messages.length === 0 && !feed.querySelector('[data-message-id]')) {
            const empty = document.createElement('p');
            empty.className = 'text-muted mb-0';
            empty.id = 'feedPlaceholder';
            empty.textContent = 'Brak wiadomości. Wyślij pierwszą wiadomość.';
            feed.appendChild(empty);
        }
    }

    async function loadMessages() {
        if (loading) {
            return;
        }
        loading = true;
        try {
            const response = await fetch('get_messages.php', { cache: 'no-store', credentials: 'same-origin' });
            const data = await readJson(response);
            if (!response.ok) {
                throw new Error(data.error || 'Nie udało się pobrać wiadomości.');
            }
            if (!data || !Array.isArray(data.messages)) {
                throw new Error('Odpowiedź serwera nie zawiera listy wiadomości.');
            }
            updateFeed(data.messages);
            setStatus(loadStatus, 'Połączono · odświeżanie co 1,8 s', false);
        } catch (error) {
            setStatus(loadStatus, error.message || 'Błąd pobierania wiadomości.', true);
        } finally {
            loading = false;
        }
    }

    async function deleteMessage(id, article, button) {
        button.disabled = true;
        const formData = new FormData();
        formData.append('idk', String(id));
        formData.append('csrf_token', form.elements.csrf_token.value);
        try {
            const response = await fetch('delete_message.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const data = await readJson(response);
            if (!response.ok) {
                throw new Error(data.error || 'Nie udało się usunąć wiadomości.');
            }
            article.remove();
            setStatus(loadStatus, data.message, false);
        } catch (error) {
            button.disabled = false;
            setStatus(loadStatus, error.message || 'Błąd usuwania wiadomości.', true);
        }
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        sendButton.disabled = true;
        setStatus(sendStatus, 'Wysyłanie...', false);
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const data = await readJson(response);
            if (!response.ok) {
                throw new Error(data.error || 'Nie udało się wysłać wiadomości.');
            }
            form.elements.message.value = '';
            form.elements.fileToUpload.value = '';
            setStatus(sendStatus, data.message, false);
            await loadMessages();
        } catch (error) {
            setStatus(sendStatus, error.message || 'Błąd wysyłania wiadomości.', true);
        } finally {
            sendButton.disabled = false;
        }
    });

    loadMessages();
    window.setInterval(loadMessages, 1800);
}());
