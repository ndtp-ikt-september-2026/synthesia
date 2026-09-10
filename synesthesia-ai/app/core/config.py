from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file='.env', env_file_encoding='utf-8')

    app_name: str = 'synesthesia-ai'
    debug: bool = False

    qdrant_host: str = 'qdrant'
    qdrant_port: int = 6333
    qdrant_collection_name: str = 'music_catalog'

    redis_url: str = 'redis://redis:6379/0'
    cache_ttl_seconds: int = 86400

    embedding_model_name: str = 'all-MiniLM-L6-v2'


settings = Settings()
