<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Chat</title>
    <link rel="stylesheet" href="css/main.css">
    <script type="text/javascript" src="js/code.jquery.com_jquery-3.7.1.min.js"></script>
</head>
<body>
<header>
    <img class="logo" src="img/logo.png" alt="Pokémon">
    <h1>Chat</h1>
</header>

<?php
// Session Check (Security)
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}
?>

<nav>
    <ul>
        <li><a id="logout" href="#">Logout</a></li>
        <li><a href="team.php">My Team</a></li>
        <li><a href="chat.php">Chat</a></li>
    </ul>
</nav>

<main>
    <section class="section">
        <h2>Nachrichten</h2>
        <!-- Chat controls (select chat partner + select send mode) -->
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <label>Chat mit:</label>
            <select id="withUser">
                <option value="0">Alle (Inbox)</option>
            </select>

            <label>Senden als:</label>
            <select id="sendMode">
                <option value="private">Private Nachricht</option>
                <option value="broadcast">Broadcast (alle)</option>
            </select>
        </div>
        <!--Messages are appended here dynamically using JavaScript -->
        <div id="chatBox" style="margin-top:12px; height:380px; overflow:auto; border:1px solid #ccc; padding:10px; border-radius:8px; background:#fff;">
            <!-- messages -->
        </div>
        <!--Submission is handled via AJAX -> no page reload -->
        <form id="sendForm" style="margin-top:12px; display:flex; gap:10px;">
            <input id="message" name="message" type="text" placeholder="Deine Nachricht..." style="flex:1;" required>
            <button type="submit">Senden</button>
        </form>

        <p id="warn" class="warning"></p>
    </section>
</main>

<script>
    let lastId = 0;
    let polling = null;

    function appendMessages(rows) {
        let box = $("#chatBox");
        for (let m of rows) {
            // Update lastId to the biggest message ID we have seen
            lastId = Math.max(lastId, parseInt(m.idMessage, 10));

            let meta = m.isBroadcast == 1
                ? `[${m.createdAt}] (Broadcast) ${m.senderName}: `
                : `[${m.createdAt}] ${m.senderName} send to ${m.receiverName ?? "?"}: `;
            // Append message line into chat box
            box.append(`<div style="margin-bottom:6px;"><b>${meta}</b>${m.message}</div>`);
        }
        // Auto-scroll to the bottom if new messages were added
        if (rows.length > 0) box.scrollTop(box[0].scrollHeight);
    }
    // LOAD USERS INTO DROPDOWN
    function loadUsers() {
        $.getJSON("php/getUsers.php").done(users => {
            for (let u of users) {
                // Add each user as selectable option
                $("#withUser").append(`<option value="${u.idTrainer}">${u.username}</option>`);
            }
        });
    }
    // Requests new messages from php/getMessages.php every second.
    function poll() {
        let withId = parseInt($("#withUser").val(), 10);
        $.getJSON("php/getMessages.php", { afterId: lastId, withId: withId })
            .done(rows => appendMessages(rows))
            .fail(() => {});
    }

    function startPolling() {
        if (polling) clearInterval(polling);
        polling = setInterval(poll, 1000); // 1x pro Sekunde
    }

    function updateModeUI() {
        let mode = $("#sendMode").val();
        let isBroadcast = (mode === "broadcast");

        // When broadcasting, selecting a user is not needed
        $("#withUser").prop("disabled", isBroadcast);

        // Optional: if broadcast is selected, reset user dropdown to Inbox
        if (isBroadcast) $("#withUser").val("0");
    }

    $(document).ready(function() {
        // Logout
        $("#logout").click(function(e){
            e.preventDefault();
            $.post("php/doLogout.php").always(() => window.location.href = "index.php");
        });

        loadUsers();
        startPolling();
        poll(); // direkt einmal laden

        // Switch chat partner: clear and reload messages
        $("#withUser").change(function(){
            $("#chatBox").html("");
            lastId = 0;
            poll();
        });

        // Switch send mode (private/broadcast)
        $("#sendMode").change(function(){
            updateModeUI();
        });

        // Init UI state
        updateModeUI();

        // Send message
        $("#sendForm").submit(function(e){
            e.preventDefault();
            $("#warn").text("");

            // Read input values
            let msg = $("#message").val();
            let withId = parseInt($("#withUser").val(), 10);
            let mode = $("#sendMode").val();

            // Payload always includes message text
            let payload = { message: msg };

            if (mode === "broadcast") {
                // Broadcast message -> goes to all users
                payload.broadcast = "1";
            } else {
                // Private message -> must have a selected receiver
                if (withId <= 0) {
                    $("#warn").text("Bitte wähle einen Nutzer oder stelle auf Broadcast um.");
                    return;
                }
                payload.idReceiver = withId;
            }

            $.post("php/sendMessage.php", payload)
                .done(() => {
                    // Clear input after success
                    $("#message").val("");
                    // Immediately fetch new messages
                    poll();
                })
                .fail((xhr) => {
                    // Show send error (backend response or status code)
                    $("#warn").text("Senden fehlgeschlagen: " + (xhr.responseText || xhr.status));
                });
        });
    });
</script>
</body>
</html>
