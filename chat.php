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

        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <label>Chat mit:</label>
            <select id="withUser">
                <option value="0">Alle (Inbox)</option>
            </select>

            <button id="broadcastBtn" type="button">Broadcast senden</button>
        </div>

        <div id="chatBox" style="margin-top:12px; height:380px; overflow:auto; border:1px solid #ccc; padding:10px; border-radius:8px; background:#fff;">
            <!-- messages -->
        </div>

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
    let broadcastMode = false;

    function appendMessages(rows) {
        const box = $("#chatBox");
        for (const m of rows) {
            lastId = Math.max(lastId, parseInt(m.idMessage, 10));

            const meta = m.isBroadcast == 1
                ? `[${m.createdAt}] (Broadcast) ${m.senderName}: `
                : `[${m.createdAt}] ${m.senderName} -> ${m.receiverName ?? "?"}: `;

            box.append(`<div style="margin-bottom:6px;"><b>${meta}</b>${m.message}</div>`);
        }
        if (rows.length > 0) box.scrollTop(box[0].scrollHeight);
    }

    function loadUsers() {
        $.getJSON("php/getUsers.php").done(users => {
            for (const u of users) {
                $("#withUser").append(`<option value="${u.idTrainer}">${u.username}</option>`);
            }
        });
    }

    function poll() {
        const withId = parseInt($("#withUser").val(), 10);
        $.getJSON("php/getMessages.php", { afterId: lastId, withId: withId })
            .done(rows => appendMessages(rows))
            .fail(() => {});
    }

    function startPolling() {
        if (polling) clearInterval(polling);
        polling = setInterval(poll, 1000); // 1x pro Sekunde
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

        $("#withUser").change(function(){
            // Wechsel: Chatbox leeren + lastId reset, dann neu laden
            $("#chatBox").html("");
            lastId = 0;
            broadcastMode = false;
            $("#broadcastBtn").text("Broadcast senden");
            poll();
        });

        $("#broadcastBtn").click(function(){
            broadcastMode = !broadcastMode;
            $(this).text(broadcastMode ? "Broadcast: AN" : "Broadcast senden");
        });

        $("#sendForm").submit(function(e){
            e.preventDefault();
            $("#warn").text("");

            const msg = $("#message").val();
            const withId = parseInt($("#withUser").val(), 10);

            const payload = { message: msg };

            if (broadcastMode) {
                payload.broadcast = "1";
            } else {
                if (withId <= 0) {
                    $("#warn").text("Bitte wähle einen Nutzer aus (oder aktiviere Broadcast).");
                    return;
                }
                payload.idReceiver = withId;
            }

            $.post("php/sendMessage.php", payload)
                .done(() => {
                    $("#message").val("");
                    poll(); // sofort aktualisieren
                })
                .fail((xhr) => {
                    $("#warn").text("Senden fehlgeschlagen: " + (xhr.responseText || xhr.status));
                });
        });
    });
</script>
</body>
</html>
