ARG PYTHON_IMAGE=python:3.12-slim@sha256:5e2bdb1b0d542d5039cffb8181c65930093949194a65c365dfb1b0fe13a4fa5b
FROM ${PYTHON_IMAGE}

ARG GLINER_MODEL=fastino/gliner2.5-multi-v1
ARG GLINER_REVISION=7b005274e82da37dcf1f5fd9d6a2ef413c6a004c

ENV HF_HOME=/opt/huggingface \
    PYTHONUNBUFFERED=1

RUN pip install --no-cache-dir --index-url https://download.pytorch.org/whl/cpu torch==2.15.0+cpu \
    && pip install --no-cache-dir gliner2==2.1.0 --no-deps \
    && pip install --no-cache-dir pydantic==2.14.3 requests==2.34.2 tqdm==4.70.1 urllib3==2.8.0 numpy==2.5.4 peft==0.21.2 safetensors==0.8.0 'transformers>=4.43,<6' \
    && HF_HUB_OFFLINE=0 python -c "from huggingface_hub import snapshot_download; snapshot_download('${GLINER_MODEL}', revision='${GLINER_REVISION}', local_files_only=False)" \
    && adduser --system --uid 65532 --group gliner \
    && chown -R gliner:gliner /opt/huggingface

ARG SOURCE_REVISION=unknown
LABEL org.opencontainers.image.revision=${SOURCE_REVISION} \
      org.opencontainers.image.source="https://github.com/curlysanders/job-application-tracker" \
      io.github.curlysanders.gliner.model=${GLINER_MODEL} \
      io.github.curlysanders.gliner.model.revision=${GLINER_REVISION}

COPY .docker/gliner2/server.py /opt/gliner/server.py

ENV HF_HUB_OFFLINE=1

USER gliner:gliner
EXPOSE 8080
ENTRYPOINT ["python", "/opt/gliner/server.py"]
