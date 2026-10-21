"""Sends fridge alerts and checks email replies for fan approval."""

import email
import imaplib
import re
import secrets
import smtplib
import threading
import time
from email.message import EmailMessage
from email.utils import parseaddr
from email import policy

import config


class EmailHandler:
    def __init__(self, set_fan_state, notify):
        # These functions are provided by controller.py so this file does
        # not need to know how the fan or desktop notifications are implemented.
        self.set_fan_state = set_fan_state
        self.notify = notify

        # A token links an email reply to one specific temperature alert.
        # Tokens are kept in memory, so restarting Python clears them.
        self.pending_alerts = {}
        self.lock = threading.Lock()

    def send_alert(self, fridge, temperature, threshold):
        """Email a fan approval request. Returns True if sending succeeded."""
        if not all([config.EMAIL_USER, config.EMAIL_PASSWORD, config.ALERT_TO]):
            print("Email is not configured; no alert email was sent.")
            return False

        # Do not create multiple active approval requests for the same fridge.
        now = time.time()
        with self.lock:
            expired = [
                token for token, alert in self.pending_alerts.items()
                if now - alert["created_at"] > 1800
            ]
            for token in expired:
                self.pending_alerts.pop(token, None)

            if any(
                alert["fridge"] == fridge
                for alert in self.pending_alerts.values()
            ):
                print(f"An approval email for {fridge} is already pending.")
                return False

            token = secrets.token_hex(16)
            self.pending_alerts[token] = {
                "fridge": fridge,
                "created_at": now,
            }

        message = EmailMessage()
        message["From"] = config.EMAIL_USER
        message["To"] = config.ALERT_TO
        message["Reply-To"] = config.EMAIL_USER
        message["Subject"] = f"[FRIDGE-ALERT:{token}] {fridge} temperature warning"
        message.set_content(
            f"The current temperature is {temperature:.1f} C for {fridge}.\n"
            f"Configured threshold: {threshold:.1f} C.\n\n"
            "Would you like to turn on the fan?\n"
            "Reply with exactly YES to approve or NO to reject."
        )

        try:
            with smtplib.SMTP(
                config.SMTP_HOST, config.SMTP_PORT, timeout=20
            ) as server:
                server.starttls()
                server.login(config.EMAIL_USER, config.EMAIL_PASSWORD)
                server.send_message(message)

            print(f"Alert email sent for {fridge}.")
            return True

        except Exception as error:
            # If sending fails, the token should not remain usable.
            with self.lock:
                self.pending_alerts.pop(token, None)
            print("Email sending failed:", type(error).__name__, str(error))
            return False

    @staticmethod
    def _get_body(message):
        """Return the plain-text part of an email."""
        if message.is_multipart():
            for part in message.walk():
                if part.get_content_type() == "text/plain" and not part.get_filename():
                    try:
                        return part.get_content()
                    except Exception:
                        pass
            return ""

        if message.get_content_type() == "text/plain":
            try:
                return message.get_content()
            except Exception:
                return ""

        return ""

    @staticmethod
    def _normalize_reply(body):
        """Accept only a short reply whose text is exactly YES or NO."""
        lines = []
        for line in body.splitlines():
            line = line.strip()

            # Email apps often include the previous message below the reply.
            if line.startswith(">") or line.lower().startswith(
                ("on ", "from:", "sent:", "to:", "subject:")
            ):
                break

            if line:
                lines.append(line)

        answer = " ".join(lines).strip().upper()
        return answer if answer in ("YES", "NO") else ""

    def _handle_reply(self, token, sender, body):
        # Ignore replies from any address other than the approved sender.
        if sender.strip().lower() != config.ALLOWED_REPLY_FROM:
            print("Ignoring email reply from an unapproved sender.")
            return

        with self.lock:
            alert = self.pending_alerts.get(token)

            if alert is None:
                print("Ignoring email reply: approval is not pending.")
                return

            # Approval links expire after 30 minutes.
            if time.time() - alert["created_at"] > 1800:
                self.pending_alerts.pop(token, None)
                print("Ignoring email reply: approval expired.")
                return

            answer = self._normalize_reply(body)
            if answer not in ("YES", "NO"):
                print("Reply must contain exactly YES or NO. No action taken.")
                return

            # Consume the token so it cannot be used twice.
            self.pending_alerts.pop(token, None)

        # Do the fan action after releasing the lock.
        if answer == "YES":
            self.set_fan_state(True, source="email")
            self.notify("Fan approved", f"Fan approved for {alert['fridge']}.")
        else:
            self.notify("Fan not activated", f"Fan was not activated for {alert['fridge']}.")
            print("Fan activation rejected.")

    def check_replies(self):
        """Background worker: check unread inbox messages every few seconds."""
        if not all([
            config.EMAIL_USER,
            config.EMAIL_PASSWORD,
            config.ALLOWED_REPLY_FROM,
        ]):
            print("Email reply checking is disabled; configure email settings.")
            return

        while True:
            mail = None
            try:
                mail = imaplib.IMAP4_SSL(config.IMAP_HOST, config.IMAP_PORT)
                mail.login(config.EMAIL_USER, config.EMAIL_PASSWORD)
                mail.select("INBOX")

                status, data = mail.search(None, "UNSEEN")
                if status == "OK":
                    for message_id in data[0].split():
                        status, parts = mail.fetch(message_id, "(RFC822)")
                        if status != "OK" or not parts or not isinstance(parts[0], tuple):
                            continue

                        message = email.message_from_bytes(parts[0][1],policy=policy.default,)
                        subject = message.get("Subject", "")

                        # Only read messages that contain a token from our alert emails.
                        match = re.search(
                            r"\[FRIDGE-ALERT:([a-f0-9]{32})\]",
                            subject,
                            re.IGNORECASE,
                        )
                        if not match:
                            continue

                        token = match.group(1).lower()
                        sender = parseaddr(message.get("From", ""))[1]
                        body = self._get_body(message)
                        self._handle_reply(token, sender, body)
                        mail.store(message_id, "+FLAGS", "\\Seen")

            except Exception as error:
                print("Email checking error:", type(error).__name__, str(error))

            finally:
                if mail is not None:
                    try:
                        mail.logout()
                    except Exception:
                        pass

            time.sleep(config.EMAIL_POLL_SECONDS)
