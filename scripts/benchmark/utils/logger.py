import settings
from utils.io import safe_write


def log_error(content: str):
    safe_write(content, settings.LOG_FILE_ERROR, True)