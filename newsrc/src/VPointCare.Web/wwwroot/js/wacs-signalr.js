window.wacsSignalR = {
    connection: null,
    heartbeat: null,

    startInbox: async function (dotNetRef) {
        if (!window.signalR) {
            return { ok: false, message: "SignalR client belum termuat." };
        }

        if (this.connection) {
            return { ok: true, message: "connected" };
        }

        const connection = new signalR.HubConnectionBuilder()
            .withUrl("/hubs/waha-inbox")
            .withAutomaticReconnect()
            .build();

        connection.on("InboxUpdated", payload => dotNetRef.invokeMethodAsync("OnInboxUpdated", payload));
        connection.on("NewMessageNotification", payload => {
            window.wacsNotifications.playNotificationSound();
            dotNetRef.invokeMethodAsync("OnNewMessageNotification", payload);
        });
        connection.on("AgentsUpdated", payload => dotNetRef.invokeMethodAsync("OnAgentsUpdated", payload));

        await connection.start();
        this.connection = connection;
        this.heartbeat = window.setInterval(() => connection.invoke("Heartbeat"), 30000);
        return { ok: true, message: "connected" };
    },

    stopInbox: async function () {
        if (this.heartbeat) {
            window.clearInterval(this.heartbeat);
            this.heartbeat = null;
        }

        if (this.connection) {
            const connection = this.connection;
            this.connection = null;
            await connection.stop();
        }
    }
};
