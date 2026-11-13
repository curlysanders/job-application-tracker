from __future__ import annotations

import json
import os
from http import HTTPStatus
from http.server import BaseHTTPRequestHandler, HTTPServer

from gliner2 import AutoExtractor


MAX_TEXT_CHARACTERS = 100_000
MAX_REQUEST_BYTES = 512_000
MAX_ENTITIES_PER_LABEL = 12
MODEL_ID = "fastino/gliner2.5-multi-v1"
MODEL_REVISION = "7b005274e82da37dcf1f5fd9d6a2ef413c6a004c"
MODEL_PATH = "/opt/huggingface/hub/models--fastino--gliner2.5-multi-v1/snapshots/" + MODEL_REVISION
LABELS = {
    "title": "The advertised job title or role, without employer or location.",
    "company": "The employer or hiring organization, not a staffing intermediary.",
    "recruiter": "A named recruiting agency or staffing intermediary. Never return the generic word recruiter by itself.",
    "location": "The stated physical work location, city, or region.",
    "technology": "A required programming language, framework, database, cloud platform, or technical tool.",
}


MODEL = AutoExtractor.from_pretrained(MODEL_PATH, map_location="cpu")


class Handler(BaseHTTPRequestHandler):
    server_version = "GlinerVacancySidecar/1"

    def do_GET(self):
        if self.path != "/healthz":
            self._json(HTTPStatus.NOT_FOUND, {"status": "not_found"})
            return
        self._json(HTTPStatus.OK, {"status": "ready", "model": MODEL_ID, "revision": MODEL_REVISION})

    def do_POST(self):
        if self.path != "/v1/vacancy-extractions":
            self._json(HTTPStatus.NOT_FOUND, {"error": "not_found"})
            return
        content_length = self.headers.get("Content-Length")
        if content_length is None or not content_length.isdecimal() or int(content_length) > MAX_REQUEST_BYTES:
            self._json(HTTPStatus.REQUEST_ENTITY_TOO_LARGE, {"error": "invalid_request"})
            return
        try:
            payload = json.loads(self.rfile.read(int(content_length)))
            text = payload["text"]
        except (json.JSONDecodeError, KeyError, TypeError, UnicodeDecodeError):
            self._json(HTTPStatus.BAD_REQUEST, {"error": "invalid_request"})
            return
        if not isinstance(text, str) or not text.strip() or len(text) > MAX_TEXT_CHARACTERS:
            self._json(HTTPStatus.BAD_REQUEST, {"error": "invalid_request"})
            return

        result = MODEL.extract_entities_long(
            text,
            LABELS,
            chunk_size=384,
            chunk_overlap=64,
            include_confidence=True,
            include_spans=True,
        )
        entities = []
        for kind, matches in result.get("entities", {}).items():
            for match in matches[:MAX_ENTITIES_PER_LABEL]:
                entities.append({
                    "kind": kind,
                    "text": match["text"],
                    "start": match["start"],
                    "end": match["end"],
                    "confidence": match["confidence"],
                })
        entities.sort(key=lambda item: (item["kind"], -item["confidence"], item["start"]))
        self._json(HTTPStatus.OK, {"model": MODEL_ID, "revision": MODEL_REVISION, "entities": entities})

    def log_message(self, _format, *_args):
        # Do not log request paths with query data, headers, or vacancy content.
        return

    def _json(self, status, payload):
        body = json.dumps(payload, separators=(",", ":")).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)


if __name__ == "__main__":
    HTTPServer(("0.0.0.0", int(os.environ.get("GLINER_PORT", "8080"))), Handler).serve_forever()
