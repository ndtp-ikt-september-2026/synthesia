from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file='.env', env_file_encoding='utf-8', extra='ignore')

    app_name: str = 'soundnet-ai'
    debug: bool = False

    qdrant_host: str = '127.0.0.1'
    qdrant_port: int = 6333
    qdrant_collection_name: str = 'soundnet_catalog'

    redis_host: str = '127.0.0.1'
    redis_port: int = 6379
    redis_db: int = 0
    redis_url: str | None = None
    cache_ttl_seconds: int = 86400

    embedding_model_name: str = 'paraphrase-multilingual-MiniLM-L12-v2'
    internal_secret: str = 'soundnet_secret_key'

    @property
    def effective_redis_url(self) -> str:
        if self.redis_url:
            return self.redis_url
        return f'redis://{self.redis_host}:{self.redis_port}/{self.redis_db}'


settings = Settings()
