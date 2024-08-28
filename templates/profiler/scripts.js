function showEditField(messageId) {
    //Find the td block belonging to the messageId
    const td = document.querySelector('td[data-message-block="' + messageId + '"]');

    //If the td block already contains a div container, do nothing
    if (td.querySelector('div[data-container="' + messageId + '"]')) {
        return;
    }

    //Get the current message (text content)
    const messageSpan = document.querySelector('span[data-message-span="' + messageId + '"]');
    const message = messageSpan.textContent;

    //Create a new textarea element
    const textarea = document.createElement('textarea');
    textarea.value = message;
    textarea.rows = 5;
    //Fill the column width
    textarea.style.width = '100%';

    //Hide the message span
    messageSpan.style.display = 'none';

    //Create a save button
    const saveButton = document.createElement('button');
    saveButton.textContent = 'Save';
    saveButton.classList.add('btn', 'btn-sm');

    //Add an event listener to the save button
    saveButton.addEventListener('click', function() {
        const newMessage = textarea.value;
        messageSpan.textContent = newMessage;
        //Make the message span visible again
        messageSpan.style.display = 'inline';
        td.removeChild(td.querySelector('div[data-container="' + messageId + '"]'));
        submitEditField(messageId, newMessage);
    });


    //Create a fresh div container, which contains all our elements
    const container = document.createElement('div');
    container.dataset.container = messageId;
    container.appendChild(textarea);
    container.appendChild(saveButton);

    //Append the textarea to the td block
    td.appendChild(container);
}

function submitEditField(messageId, message) {
    const td = document.querySelector('td[data-message-block="' + messageId + '"]');

    //Make the td light blue, to indicate that the message is being submitted
    td.style.backgroundColor = 'lightblue';

    const errorHandler = () => {
        alert('Could not update message');
        td.style.backgroundColor = 'red';
        setTimeout(function() {
            td.style.backgroundColor = '';
        }, 1000);
    }

    //Make a POST request to the server to update the message
    fetch('/profiler/update-message', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            messageId: messageId,
            message: message,
        }),
    }).then((response) => {
        if (!response.ok) {
           errorHandler();
           console.error(response);
           return;
        }

        //Make the td background blink green to indicate that the message has been updated
        td.style.backgroundColor = 'green';
        setTimeout(function() {
            td.style.backgroundColor = '';
        }, 300);
    }).catch((error) => {
        console.error(error);
        errorHandler();
    }
    );

    //Make the td blink to indicate that the message has been updated


}