import axios from 'axios';
interface StagedFile {
    file: File;
    newName: string;
}
export function upload(): void {
    const form = document.getElementById('upload-form') as HTMLFormElement | null;
    const fileInput = document.getElementById('files_to_upload') as HTMLInputElement | null;
    const previewContainer = document.getElementById('file-preview-container') as HTMLDivElement | null;
    const uploadButton = document.getElementById('upload-button') as HTMLButtonElement | null;
    const progressBarContainer = document.getElementById('progress-container') as HTMLDivElement | null;
    const progressBar = document.getElementById('progress-bar') as HTMLDivElement | null;
    const uploadStatus = document.getElementById('upload-status') as HTMLDivElement | null;

    if (!form || !fileInput || !previewContainer || !uploadButton || !progressBarContainer || !progressBar || !uploadStatus) {
        //console.warn('upload : one or more uploader elements are missing. Abort');
        return;
    }

    let stagedFiles: StagedFile[] = []; 

    /**
     * Formats bytes into a human-readable string (KB, MB, etc.).
     */
    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    /**
     * Renders the preview list based on the current stagedFiles array.
     */
    const renderPreviews = () => {
        previewContainer.innerHTML = '';
        
        stagedFiles.forEach((stagedFile, index) => {
            const previewWrapper = document.createElement('div');
            previewWrapper.className = 'flex items-center justify-between p-2 border border-stone-300 rounded shadow gap-4';

            const thumbnail = document.createElement('img');
            thumbnail.className = 'w-12 h-12 object-cover rounded';
            if (stagedFile.file.type.startsWith('image/')) {
                thumbnail.src = URL.createObjectURL(stagedFile.file);
            }

            const fileInfo = document.createElement('div');
            fileInfo.className = 'flex-1';
            
            const nameSpan = document.createElement('p');
            nameSpan.className = 'font-bold text-sm';
            nameSpan.textContent = stagedFile.newName;

            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.className = 'font-bold border rounded p-1 w-full hidden text-sm';
            nameInput.value = stagedFile.newName;

            const sizeP = document.createElement('p');
            sizeP.className = 'text-xs text-gray-500';
            sizeP.textContent = formatFileSize(stagedFile.file.size);

            fileInfo.appendChild(nameSpan);
            fileInfo.appendChild(nameInput);
            fileInfo.appendChild(sizeP);

            const buttonGroup = document.createElement('div');
            buttonGroup.className = 'flex items-center gap-2';

            const renameButton = document.createElement('button');
            renameButton.className = 'text-blue-500 hover:text-blue-700 text-lg';
            renameButton.innerHTML = '✏️';
            renameButton.type = 'button';

            const removeButton = document.createElement('button');
            removeButton.className = 'text-red-500 hover:text-red-700 font-bold text-xl';
            removeButton.innerHTML = '❌';
            removeButton.type = 'button';

            renameButton.onclick = () => {
                nameSpan.classList.toggle('hidden');
                nameInput.classList.toggle('hidden');
                nameInput.focus();
                nameInput.select();
            };

            const saveNewName = () => {
                stagedFile.newName = nameInput.value;
                nameSpan.textContent = nameInput.value;
                nameSpan.classList.remove('hidden');
                nameInput.classList.add('hidden');
            };

            nameInput.addEventListener('blur', saveNewName);
            nameInput.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveNewName();
                } else if (e.key === 'Escape') {
                    nameInput.value = stagedFile.newName;
                    renameButton.click();
                }
            });

            removeButton.onclick = () => {
                stagedFiles.splice(index, 1);
                const dataTransfer = new DataTransfer();

                stagedFiles.forEach(stagedFile => {
                    dataTransfer.items.add(stagedFile.file);
                });

                if (fileInput) {
                    fileInput.files = dataTransfer.files;
                }
                renderPreviews(); 
            };

            buttonGroup.appendChild(renameButton);
            buttonGroup.appendChild(removeButton);

            previewWrapper.appendChild(thumbnail);
            previewWrapper.appendChild(fileInfo);
            previewWrapper.appendChild(buttonGroup);
            previewContainer.appendChild(previewWrapper);
        });

        uploadButton.disabled = stagedFiles.length === 0;
    };

    const handleFiles = (files: FileList) => {
        stagedFiles = Array.from(files).map(file => ({
            file: file,
            newName: file.name,
        }));
        renderPreviews();
    };

    fileInput.addEventListener('change', () => {
        if (fileInput.files) {
            handleFiles(fileInput.files);
        }
    });

    form.addEventListener('dragover', (event) => {
        event.preventDefault();
        form.classList.add('is-dragover');
    });

    form.addEventListener('dragleave', (event) => {
        event.preventDefault();
        form.classList.remove('is-dragover');
    });

    form.addEventListener('drop', (event) => {
        event.preventDefault();
        form.classList.remove('is-dragover');
        const files = event.dataTransfer?.files;
        if (files) {
            handleFiles(files);
            fileInput.files = files;
        }
    });

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        if (stagedFiles.length === 0) return;

        const formData = new FormData();
        stagedFiles.forEach(stagedFile => {
            formData.append('files_to_upload[]', stagedFile.file, stagedFile.newName);
        });

        progressBarContainer.classList.remove('hidden');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        uploadStatus.textContent = 'Uploading...';
        uploadButton.disabled = true;

        axios.post(form.action, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = `${percent}%`;
                    progressBar.textContent = `${percent}%`;
                }
            },
        })
        .then(response => {
            uploadStatus.textContent = response.data.message;
            setTimeout(() => {
                const path = form.action.split('/upload/')[1] || '';
                window.location.href = `/admin/files/${path}`;
            }, 1500);
        })
        .catch(error => {
            uploadStatus.textContent = `Upload failed : ${error}`;
            uploadStatus.style.color = 'red';
            uploadButton.disabled = false; 
        });
    });
}